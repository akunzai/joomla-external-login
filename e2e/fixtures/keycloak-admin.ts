import { request, type APIRequestContext } from '@playwright/test';

const KEYCLOAK_URL = 'https://auth.dev.local:8443';
const REALM = 'demo';
// Compose's KC_BOOTSTRAP_ADMIN_* defaults; override when .devcontainer/.env changes them.
const ADMIN_USERNAME = process.env.KC_BOOTSTRAP_ADMIN_USERNAME ?? 'admin';
const ADMIN_PASSWORD = process.env.KC_BOOTSTRAP_ADMIN_PASSWORD ?? 'admin';

/**
 * Minimal Keycloak Admin REST client, so a test can create its own users and change their
 * email between logins (simulating a username claim that changed at the provider).
 */
export class KeycloakAdmin {
  private constructor(private readonly api: APIRequestContext) {}

  static async connect(): Promise<KeycloakAdmin> {
    const tokenContext = await request.newContext({ baseURL: KEYCLOAK_URL, ignoreHTTPSErrors: true });
    const tokenResponse = await tokenContext.post('/realms/master/protocol/openid-connect/token', {
      form: { grant_type: 'password', client_id: 'admin-cli', username: ADMIN_USERNAME, password: ADMIN_PASSWORD },
    });
    if (!tokenResponse.ok()) {
      throw new Error(`Keycloak admin token request failed: ${tokenResponse.status()}`);
    }
    const { access_token: token } = await tokenResponse.json();
    await tokenContext.dispose();

    const api = await request.newContext({
      baseURL: `${KEYCLOAK_URL}/admin/realms/${REALM}/`,
      ignoreHTTPSErrors: true,
      extraHTTPHeaders: { Authorization: `Bearer ${token}` },
    });

    return new KeycloakAdmin(api);
  }

  /** Creates a user with a verified email and returns its id (the OIDC `sub`). */
  async createUser(username: string, email: string, password: string): Promise<string> {
    const response = await this.api.post('users', {
      data: {
        username,
        email,
        emailVerified: true,
        enabled: true,
        firstName: 'Subject',
        lastName: username,
        credentials: [{ type: 'password', value: password, temporary: false }],
      },
    });
    if (response.status() !== 201) {
      throw new Error(`Creating Keycloak user ${username} failed: ${response.status()} ${await response.text()}`);
    }

    return response.headers().location.split('/').pop()!;
  }

  async setEmail(userId: string, email: string): Promise<void> {
    const response = await this.api.put(`users/${userId}`, { data: { email, emailVerified: true } });
    if (!response.ok()) {
      throw new Error(`Updating Keycloak user ${userId} failed: ${response.status()} ${await response.text()}`);
    }
  }

  async deleteUser(userId: string): Promise<void> {
    await this.api.delete(`users/${userId}`);
  }

  async dispose(): Promise<void> {
    await this.api.dispose();
  }
}
