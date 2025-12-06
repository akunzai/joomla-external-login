import { type Page, type Locator } from '@playwright/test';

export class LogsPage {
  readonly page: Page;
  readonly heading: Locator;
  readonly logTable: Locator;
  readonly searchInput: Locator;
  readonly searchButton: Locator;
  readonly clearButton: Locator;
  readonly paginationInfo: Locator;

  constructor(page: Page) {
    this.page = page;
    this.heading = page.locator('h1:has-text("Logs")');
    this.logTable = page.locator('table.itemList, table.table');
    this.searchInput = page.getByRole('textbox', { name: /Search/i });
    this.searchButton = page.getByRole('button', { name: 'Search' });
    this.clearButton = page.getByRole('button', { name: 'Clear' });
    this.paginationInfo = page.locator('nav.pagination, .pagination-counter');
  }

  async goto() {
    await this.page.goto('/administrator/index.php?option=com_externallogin&view=logs');
  }

  async getLogCount(): Promise<number> {
    const rows = this.page.locator('table tbody tr');
    return rows.count();
  }

  async searchLog(text: string) {
    await this.searchInput.fill(text);
    await this.searchButton.click();
  }

  async clearSearch() {
    await this.clearButton.click();
  }

  async getLogRow(text: string): Promise<Locator> {
    return this.page.locator('tr').filter({ hasText: text });
  }

  async isLogVisible(text: string): Promise<boolean> {
    const row = await this.getLogRow(text);
    return row.isVisible();
  }
}
