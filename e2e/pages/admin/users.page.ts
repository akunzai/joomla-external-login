import { type Page, type Locator } from '@playwright/test';

export class UsersPage {
  readonly page: Page;
  readonly heading: Locator;
  readonly userTable: Locator;
  readonly searchInput: Locator;
  readonly searchButton: Locator;
  readonly clearButton: Locator;
  readonly paginationInfo: Locator;

  constructor(page: Page) {
    this.page = page;
    this.heading = page.locator('h1:has-text("Users")');
    this.userTable = page.locator('table.itemList, table.table');
    this.searchInput = page.getByRole('textbox', { name: /Search/i });
    this.searchButton = page.getByRole('button', { name: 'Search' });
    this.clearButton = page.getByRole('button', { name: 'Clear' });
    this.paginationInfo = page.locator('nav.pagination, .pagination-counter');
  }

  async goto() {
    await this.page.goto('/administrator/index.php?option=com_externallogin&view=users');
  }

  async getUserCount(): Promise<number> {
    const rows = this.page.locator('table tbody tr');
    return rows.count();
  }

  async searchUser(username: string) {
    await this.searchInput.fill(username);
    await this.searchButton.click();
    // Wait for search results to load
    await this.page.waitForLoadState('networkidle');
  }

  async clearSearch() {
    await this.clearButton.click();
  }

  async getUserRow(username: string): Promise<Locator> {
    // Match table body rows that contain the username
    return this.page.locator('table tbody tr, table tr').filter({ hasText: username });
  }

  async isUserVisible(username: string): Promise<boolean> {
    const row = await this.getUserRow(username);
    // Wait up to 5 seconds for the row to appear
    try {
      await row.first().waitFor({ state: 'visible', timeout: 5000 });
      return true;
    } catch {
      return false;
    }
  }
}
