<?php

namespace Drupal\Tests\devel_generate_commerce\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Devel Generate Commerce Module Load Test.
 *
 * @group devel_generate_commerce
 */
class ModuleLoadTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'block', 'devel_generate_commerce'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests Homepage after enabling Devel Generate Commerce Module.
   */
  public function testHomepage() {

    // Test homepage.
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);

    // Minimal homepage title.
    $this->assertSession()->pageTextContains('Log in');
  }

  /**
   * Tests the Devel Generate Commerce module unistall.
   */
  public function testModuleUninstall() {

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer modules',
    ]);

    // Uninstall the module.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/modules/uninstall');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Devel Generate Commerce');
    $this->submitForm(['uninstall[devel_generate_commerce]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');
    $this->assertSession()->pageTextNotContains('Devel Generate Commerce');

    // Visit the frontpage.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests Devel Generate Commerce module reinstalling after being uninstalled.
   */
  public function testReinstallAfterUninstall() {

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer modules',
    ]);

    // Uninstall the module.
    $this->drupalLogin($admin_user);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Uninstall the Devel Generate Commerce module.
    $this->container->get('module_installer')->uninstall(['devel_generate_commerce'], FALSE);

    $this->drupalGet('/admin/modules');
    $page->checkField('modules[devel_generate_commerce][enable]');
    $page->pressButton('Install');
    $assert_session->pageTextNotContains('Unable to install Devel Generate Commerce');
    $assert_session->pageTextContains('Module Devel Generate Commerce has been enabled');
  }

}
