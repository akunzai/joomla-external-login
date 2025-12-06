import { type Page, type Locator } from '@playwright/test';

export class ServersPage {
  readonly page: Page;
  readonly heading: Locator;
  readonly newButton: Locator;
  readonly editButton: Locator;
  readonly publishButton: Locator;
  readonly unpublishButton: Locator;
  readonly trashButton: Locator;
  readonly serverTable: Locator;
  readonly checkAllCheckbox: Locator;

  constructor(page: Page) {
    this.page = page;
    this.heading = page.getByRole('heading', { name: /Servers manager/i });
    this.newButton = page.getByRole('button', { name: 'New' });
    this.editButton = page.getByRole('button', { name: 'Edit' });
    this.publishButton = page.getByRole('button', { name: 'Publish', exact: true });
    this.unpublishButton = page.getByRole('button', { name: 'Unpublish', exact: true });
    this.trashButton = page.getByRole('button', { name: 'Trash' });
    this.serverTable = page.locator('table.itemList, table.table');
    this.checkAllCheckbox = page.getByRole('checkbox', { name: 'Check All Items' });
  }

  async goto() {
    await this.page.goto('/administrator/index.php?option=com_externallogin&view=servers');
  }

  async getServerRow(title: string): Promise<Locator> {
    return this.page.locator('tr').filter({ hasText: title });
  }

  async selectServer(title: string) {
    const row = await this.getServerRow(title);
    await row.getByRole('checkbox').check();
  }

  async clickServerLink(title: string) {
    await this.page.getByRole('link', { name: title, exact: true }).click();
  }

  async getServerCount(): Promise<number> {
    const rows = this.page.locator('table tbody tr');
    return rows.count();
  }

  async isServerPublished(title: string): Promise<boolean> {
    const row = await this.getServerRow(title);
    const publishIcon = row.locator('a[href*="publish"]');
    return publishIcon.isVisible();
  }

  async toggleServerStatus(title: string) {
    const row = await this.getServerRow(title);
    const statusLink = row.locator('a[href*="servers#"]').first();
    await statusLink.click();
  }

  async deleteServer(title: string) {
    await this.selectServer(title);
    await this.trashButton.click();
  }

  async publishServer(title: string) {
    await this.selectServer(title);
    await this.publishButton.click();
  }

  async unpublishServer(title: string) {
    await this.selectServer(title);
    await this.unpublishButton.click();
  }

  async clickNew() {
    await this.newButton.click();
  }
}
