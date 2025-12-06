import { type Page, type Locator } from '@playwright/test';

export class ServerEditPage {
  readonly page: Page;
  readonly heading: Locator;
  readonly saveButton: Locator;
  readonly saveCloseButton: Locator;
  readonly closeButton: Locator;
  readonly titleInput: Locator;
  readonly statusSelect: Locator;
  readonly autoRegisterYes: Locator;
  readonly autoRegisterNo: Locator;
  readonly autoUpdateYes: Locator;
  readonly autoUpdateNo: Locator;

  constructor(page: Page) {
    this.page = page;
    this.heading = page.getByRole('heading', { name: /Servers Manager/i });
    this.saveButton = page.getByRole('button', { name: 'Save' }).first();
    this.saveCloseButton = page.getByRole('button', { name: 'Save & Close' });
    this.closeButton = page.getByRole('button', { name: 'Close', exact: true });
    this.titleInput = page.getByRole('textbox', { name: 'Title' });
    this.statusSelect = page.getByRole('combobox', { name: 'Status' });
    this.autoRegisterYes = page.locator('fieldset:has-text("Auto-register")').getByRole('radio', { name: 'Yes' });
    this.autoRegisterNo = page.locator('fieldset:has-text("Auto-register")').getByRole('radio', { name: 'No' });
    this.autoUpdateYes = page.locator('fieldset:has-text("Auto-update")').getByRole('radio', { name: 'Yes' });
    this.autoUpdateNo = page.locator('fieldset:has-text("Auto-update")').getByRole('radio', { name: 'No' });
  }

  async goto(serverId: number) {
    await this.page.goto(`/administrator/index.php?option=com_externallogin&task=server.edit&id=${serverId}`);
  }

  async setTitle(title: string) {
    await this.titleInput.fill(title);
  }

  async setStatus(status: 'Published' | 'Unpublished' | 'Archived' | 'Trashed') {
    await this.statusSelect.selectOption(status);
  }

  async setAutoRegister(enabled: boolean) {
    if (enabled) {
      await this.autoRegisterYes.check();
    } else {
      await this.autoRegisterNo.check();
    }
  }

  async setAutoUpdate(enabled: boolean) {
    if (enabled) {
      await this.autoUpdateYes.check();
    } else {
      await this.autoUpdateNo.check();
    }
  }

  async save() {
    await this.saveButton.click();
  }

  async saveAndClose() {
    await this.saveCloseButton.click();
  }

  async close() {
    await this.closeButton.click();
  }

  async getTitle(): Promise<string> {
    return this.titleInput.inputValue();
  }
}
