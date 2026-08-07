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
  readonly regexUserInput: Locator;
  readonly regexEmailInput: Locator;
  readonly groupXpathInput: Locator;
  readonly emailVerifiedXpathInput: Locator;
  readonly groupsClaimInput: Locator;
  readonly groupIntegerYes: Locator;
  readonly groupIntegerNo: Locator;
  readonly groupSeparatorInput: Locator;
  readonly autologinYes: Locator;
  readonly autologinNo: Locator;

  constructor(page: Page) {
    this.page = page;
    this.heading = page.getByRole('heading', { name: /Servers Manager/i });
    this.saveButton = page.locator('button[data-task="server.apply"], button.button-apply, button.btn-success').or(page.getByRole('button', { name: 'Save', exact: true })).first();
    this.saveCloseButton = page.getByRole('button', { name: 'Save & Close' });
    this.closeButton = page.getByRole('button', { name: 'Close', exact: true });
    this.titleInput = page.getByRole('textbox', { name: 'Title' });
    this.statusSelect = page.getByRole('combobox', { name: 'Status' });
    this.autoRegisterYes = page.locator('fieldset:has-text("Auto-register")').getByRole('radio', { name: 'Yes' });
    this.autoRegisterNo = page.locator('fieldset:has-text("Auto-register")').getByRole('radio', { name: 'No' });
    this.autoUpdateYes = page.locator('fieldset:has-text("Auto-update")').getByRole('radio', { name: 'Yes' });
    this.autoUpdateNo = page.locator('fieldset:has-text("Auto-update")').getByRole('radio', { name: 'No' });
    this.regexUserInput = page.locator('input[name="jform[params][regex_user]"]');
    this.regexEmailInput = page.locator('input[name="jform[params][regex_email]"]');
    this.groupXpathInput = page.locator('textarea[name="jform[params][group_xpath]"], input[name="jform[params][group_xpath]"]');
    this.emailVerifiedXpathInput = page.locator('textarea[name="jform[params][email_verified_xpath]"], input[name="jform[params][email_verified_xpath]"]');
    this.groupsClaimInput = page.locator('input[name="jform[params][groups_claim]"]');
    this.groupIntegerYes = page.locator('fieldset:has-text("Group Integer"), fieldset:has-text("Integer Groups")').getByRole('radio', { name: 'Yes' });
    this.groupIntegerNo = page.locator('fieldset:has-text("Group Integer"), fieldset:has-text("Integer Groups")').getByRole('radio', { name: 'No' });
    this.groupSeparatorInput = page.locator('input[name="jform[params][group_separator]"]');
    // Connection-tab "Automatic login" (autologin) radios — match by the param name so
    // we don't confuse them with the similarly labelled "Automatic logout" radios.
    this.autologinYes = page.locator('input[name="jform[params][autologin]"][value="1"]');
    this.autologinNo = page.locator('input[name="jform[params][autologin]"][value="0"]');
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

  async clickTab(tabName: string | RegExp) {
    const tab = this.page.locator('#configTabs button, #configTabs a, button[role="tab"], a[role="tab"], .nav-tabs button, .nav-tabs a, .nav-tabs .nav-link').filter({ hasText: tabName });
    if (await tab.count() > 0) {
      await tab.first().click();
      await this.page.waitForTimeout(300);
    }
  }

  async setRegexUser(pattern: string) {
    await this.clickTab(/Details/i);
    await this.regexUserInput.clear();
    await this.regexUserInput.fill(pattern);
  }

  async setRegexEmail(pattern: string) {
    await this.clickTab(/Details/i);
    await this.regexEmailInput.clear();
    await this.regexEmailInput.fill(pattern);
  }

  async setGroupXpath(xpath: string) {
    await this.clickTab(/Attributes/i);
    await this.groupXpathInput.clear();
    await this.groupXpathInput.fill(xpath);
  }

  async setEmailVerifiedXpath(xpath: string) {
    await this.clickTab(/Attributes/i);
    await this.emailVerifiedXpathInput.clear();
    await this.emailVerifiedXpathInput.fill(xpath);
  }

  async setGroupsClaim(claim: string) {
    await this.clickTab(/Attributes/i);
    await this.groupsClaimInput.clear();
    await this.groupsClaimInput.fill(claim);
  }

  async setGroupInteger(enabled: boolean) {
    await this.clickTab(/Attributes/i);
    if (enabled) {
      await this.groupIntegerYes.check();
    } else {
      await this.groupIntegerNo.check();
    }
  }

  async setGroupSeparator(separator: string) {
    await this.clickTab(/Attributes/i);
    await this.groupSeparatorInput.clear();
    await this.groupSeparatorInput.fill(separator);
  }

  async setAutologin(enabled: boolean) {
    await this.clickTab(/Connection/i);
    if (enabled) {
      await this.autologinYes.check({ force: true });
    } else {
      await this.autologinNo.check({ force: true });
    }
  }

  async save() {
    await this.saveButton.click();
    await this.page.waitForLoadState('networkidle');
    await this.page.waitForTimeout(1000);
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
