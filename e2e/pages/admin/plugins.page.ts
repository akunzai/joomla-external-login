import { type Page, type Locator } from '@playwright/test';

export class PluginsPage {
  readonly page: Page;
  readonly heading: Locator;
  readonly pluginTable: Locator;
  readonly searchInput: Locator;
  readonly searchButton: Locator;
  readonly clearButton: Locator;
  readonly enableButton: Locator;
  readonly disableButton: Locator;

  constructor(page: Page) {
    this.page = page;
    this.heading = page.getByRole('heading', { name: 'Plugins' });
    this.pluginTable = page.locator('table.itemList, table.table');
    this.searchInput = page.getByRole('textbox', { name: /Search Plugins/i });
    this.searchButton = page.getByRole('button', { name: 'Search' });
    this.clearButton = page.getByRole('button', { name: 'Clear' });
    this.enableButton = page.getByRole('button', { name: 'Enable' });
    this.disableButton = page.getByRole('button', { name: 'Disable' });
  }

  async goto() {
    await this.page.goto('/administrator/index.php?option=com_plugins');
  }

  async searchPlugin(name: string) {
    await this.searchInput.fill(name);
    await this.searchButton.click();
  }

  async clearSearch() {
    await this.clearButton.click();
  }

  async getPluginRow(name: string): Promise<Locator> {
    return this.page.locator('tr').filter({ hasText: name });
  }

  async selectPlugin(name: string) {
    const row = await this.getPluginRow(name);
    await row.getByRole('checkbox').check();
  }

  async isPluginEnabled(name: string): Promise<boolean> {
    const row = await this.getPluginRow(name);
    const disableLink = row.getByRole('link', { name: 'Disable plugin' });
    return disableLink.isVisible();
  }

  async enablePlugin(name: string) {
    await this.selectPlugin(name);
    await this.enableButton.click();
  }

  async disablePlugin(name: string) {
    await this.selectPlugin(name);
    await this.disableButton.click();
  }

  async getExternalLoginPlugins(): Promise<string[]> {
    await this.searchPlugin('external');
    const rows = this.page.locator('table tbody tr');
    const count = await rows.count();
    const plugins: string[] = [];
    for (let i = 0; i < count; i++) {
      const name = await rows.nth(i).locator('a[href*="plugin.edit"]').textContent();
      if (name) plugins.push(name.trim());
    }
    return plugins;
  }
}
