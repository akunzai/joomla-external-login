import { type Page, type Locator } from '@playwright/test';

// Joomla core's own Users manager (com_users), distinct from com_externallogin's bridge
// Users list (see users.page.ts) — used to verify actual Joomla user-group assignment.
export class CoreUsersPage {
  readonly page: Page;

  constructor(page: Page) {
    this.page = page;
  }

  // Mirrors the "view users in this group" link Joomla's own Groups manager renders
  // (administrator/components/com_users/tmpl/groups/default.php).
  async gotoFilteredByGroup(groupId: number) {
    await this.page.goto(`/administrator/index.php?option=com_users&view=users&filter[group_id]=${groupId}`);
    await this.page.waitForLoadState('networkidle');
  }

  async getUserRow(identifier: string): Promise<Locator> {
    return this.page.locator('table tbody tr, table tr').filter({ hasText: identifier });
  }

  async isUserVisible(identifier: string): Promise<boolean> {
    const row = await this.getUserRow(identifier);
    try {
      await row.first().waitFor({ state: 'visible', timeout: 5000 });
      return true;
    } catch {
      return false;
    }
  }
}
