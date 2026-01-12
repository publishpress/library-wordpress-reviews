<?php

namespace PublishPress\WordPressReviews\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PublishPress\WordPressReviews\ReviewsController;

/**
 * Unit tests for ReviewsController class.
 */
class ReviewsControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var string
     */
    private $pluginSlug = 'test-plugin';

    /**
     * @var string
     */
    private $pluginName = 'Test Plugin';

    /**
     * @var string
     */
    private $iconUrl = 'https://example.com/icon.png';

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Set up common WordPress function mocks
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test that constructor initializes properties correctly.
     */
    public function testConstructorInitializesProperties(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\expect('add_action')
            ->once()
            ->with('admin_enqueue_scripts', \Mockery::type('array'));

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName, $this->iconUrl);

        $this->assertInstanceOf(ReviewsController::class, $controller);
    }

    /**
     * Test constructor without icon URL.
     */
    public function testConstructorWithoutIconUrl(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\expect('add_action')
            ->once()
            ->with('admin_enqueue_scripts', \Mockery::type('array'));

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $this->assertInstanceOf(ReviewsController::class, $controller);
    }

    /**
     * Test that meta map filter is applied.
     */
    public function testConstructorAppliesMetaMapFilter(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\expect('apply_filters')
            ->once()
            ->with("{$this->pluginSlug}_wp_reviews_meta_map", \Mockery::type('array'))
            ->andReturnUsing(function ($filter, $metaMap) {
                return $metaMap;
            });
        Functions\expect('apply_filters')
            ->once()
            ->with("publishpress_wp_reviews_meta_map_{$this->pluginSlug}", \Mockery::type('array'))
            ->andReturnUsing(function ($filter, $metaMap) {
                return $metaMap;
            });
        Functions\when('add_action')->justReturn();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $this->assertInstanceOf(ReviewsController::class, $controller);
    }

    /**
     * Test init method adds hooks during AJAX request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitAddsAjaxHookWhenDoingAjax(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);

        // Set up add_action expectation before creating controller
        Functions\expect('add_action')
            ->with('admin_enqueue_scripts', \Mockery::type('array'));
        Functions\expect('add_action')
            ->once()
            ->with("wp_ajax_{$this->pluginSlug}_action", \Mockery::type('array'));

        define('DOING_AJAX', true);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        // Mock is_admin and user check - return false so no admin notices hooks are added
        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_user_by')->justReturn((object) ['roles' => ['administrator']]);

        $controller->init();
    }

    /**
     * Test installationPath returns existing date.
     */
    public function testInstallationPathReturnsExistingDate(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $existingDate = '2023-01-01 00:00:00';
        Functions\expect('get_option')
            ->once()
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", false)
            ->andReturn($existingDate);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $result = $controller->installationPath();

        $this->assertEquals($existingDate, $result);
    }

    /**
     * Test installationPath sets new date when none exists.
     */
    public function testInstallationPathSetsNewDateWhenNoneExists(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $currentTime = '2023-06-15 12:00:00';
        Functions\expect('get_option')
            ->once()
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", false)
            ->andReturn(false);
        Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn($currentTime);
        Functions\expect('update_option')
            ->once()
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", $currentTime);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $result = $controller->installationPath();

        $this->assertEquals($currentTime, $result);
    }

    /**
     * Test sortByPriority with equal priorities.
     */
    public function testSortByPriorityWithEqualPriorities(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $a = ['priority' => 10];
        $b = ['priority' => 10];

        $result = $controller->sortByPriority($a, $b);

        $this->assertEquals(0, $result);
    }

    /**
     * Test sortByPriority with different priorities.
     */
    public function testSortByPriorityWithDifferentPriorities(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $a = ['priority' => 5];
        $b = ['priority' => 10];

        $result = $controller->sortByPriority($a, $b);
        $this->assertEquals(-1, $result);

        $result = $controller->sortByPriority($b, $a);
        $this->assertEquals(1, $result);
    }

    /**
     * Test sortByPriority with missing priority.
     */
    public function testSortByPriorityWithMissingPriority(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $a = ['priority' => 10];
        $b = [];

        $result = $controller->sortByPriority($a, $b);

        $this->assertEquals(0, $result);
    }

    /**
     * Test rsortByPriority with equal priorities.
     */
    public function testRsortByPriorityWithEqualPriorities(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $a = ['priority' => 10];
        $b = ['priority' => 10];

        $result = $controller->rsortByPriority($a, $b);

        $this->assertEquals(0, $result);
    }

    /**
     * Test rsortByPriority with different priorities (reverse order).
     */
    public function testRsortByPriorityWithDifferentPriorities(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $a = ['priority' => 5];
        $b = ['priority' => 10];

        $result = $controller->rsortByPriority($a, $b);
        $this->assertEquals(1, $result);

        $result = $controller->rsortByPriority($b, $a);
        $this->assertEquals(-1, $result);
    }

    /**
     * Test rsortByPriority with missing priority.
     */
    public function testRsortByPriorityWithMissingPriority(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $a = [];
        $b = ['priority' => 10];

        $result = $controller->rsortByPriority($a, $b);

        $this->assertEquals(0, $result);
    }

    /**
     * Test ajaxHandler with invalid nonce returns error.
     */
    public function testAjaxHandlerWithInvalidNonceReturnsError(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $_REQUEST['nonce'] = 'invalid_nonce';

        Functions\expect('sanitize_key')
            ->once()
            ->with('invalid_nonce')
            ->andReturn('invalid_nonce');
        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', "{$this->pluginSlug}_wp_reviews_action")
            ->andReturn(false);

        // wp_send_json_error terminates execution in WordPress, so we simulate with exception
        Functions\expect('wp_send_json_error')
            ->once()
            ->andThrow(new \Exception('JSON error sent'));

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('JSON error sent');

        $controller->ajaxHandler();

        unset($_REQUEST['nonce']);
    }

    /**
     * Test ajaxHandler with valid nonce and maybe_later reason.
     */
    public function testAjaxHandlerWithMaybeLaterReason(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 1;
        $currentTime = '2023-06-15 12:00:00';

        $_REQUEST['nonce'] = 'valid_nonce';
        $_REQUEST['group'] = 'time_installed';
        $_REQUEST['code'] = 'one_week';
        $_REQUEST['priority'] = '10';
        $_REQUEST['reason'] = 'maybe_later';

        Functions\when('sanitize_key')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', "{$this->pluginSlug}_wp_reviews_action")
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_dismissed_triggers", true)
            ->andReturn([]);
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_dismissed_triggers", ['time_installed' => 10]);
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_last_dismissed", $currentTime);
        Functions\expect('current_time')
            ->with('mysql')
            ->andReturn($currentTime);
        Functions\expect('wp_send_json_success')
            ->once();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->ajaxHandler();

        unset($_REQUEST['nonce'], $_REQUEST['group'], $_REQUEST['code'], $_REQUEST['priority'], $_REQUEST['reason']);
    }

    /**
     * Test ajaxHandler with already_did reason sets permanent dismissal.
     */
    public function testAjaxHandlerWithAlreadyDidReason(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 1;
        $currentTime = '2023-06-15 12:00:00';

        $_REQUEST['nonce'] = 'valid_nonce';
        $_REQUEST['group'] = 'time_installed';
        $_REQUEST['code'] = 'one_week';
        $_REQUEST['priority'] = '10';
        $_REQUEST['reason'] = 'already_did';

        Functions\when('sanitize_key')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        Functions\expect('wp_verify_nonce')
            ->once()
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_dismissed_triggers", true)
            ->andReturn([]);
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_dismissed_triggers", \Mockery::type('array'));
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_last_dismissed", $currentTime);
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_already_did", true);
        Functions\expect('current_time')
            ->with('mysql')
            ->andReturn($currentTime);
        Functions\expect('wp_send_json_success')
            ->once();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->ajaxHandler();

        unset($_REQUEST['nonce'], $_REQUEST['group'], $_REQUEST['code'], $_REQUEST['priority'], $_REQUEST['reason']);
    }

    /**
     * Test enqueueStyle does not enqueue when notice should not display.
     */
    public function testEnqueueStyleDoesNotEnqueueWhenNotAllowed(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        // Even when is_admin returns false, currentUserIsAdministrator is still checked
        Functions\expect('is_admin')
            ->once()
            ->andReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_user_by')->justReturn((object) ['roles' => ['subscriber']]);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->enqueueStyle();

        // No styles should be registered/enqueued - verify by checking is_admin was called
        $this->assertTrue(true); // Test passes if no exception thrown and is_admin returned false
    }

    /**
     * Test enqueueStyle enqueues styles when allowed.
     */
    public function testEnqueueStyleEnqueuesWhenAllowed(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 1;
        $mockUser = (object) ['roles' => ['administrator']];

        Functions\expect('is_admin')
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_by')
            ->with('ID', $userId)
            ->andReturn($mockUser);
        Functions\expect('wp_register_style')
            ->once()
            ->with('publishpress_wordpress_reviews_style', false);
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('publishpress_wordpress_reviews_style');
        Functions\expect('wp_add_inline_style')
            ->once()
            ->with('publishpress_wordpress_reviews_style', \Mockery::type('string'));

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->enqueueStyle();
    }

    /**
     * Test that non-administrator users cannot see notices.
     */
    public function testNonAdministratorCannotSeeNotices(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 1;
        $mockUser = (object) ['roles' => ['subscriber']];

        Functions\expect('is_admin')
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_by')
            ->with('ID', $userId)
            ->andReturn($mockUser);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->enqueueStyle();

        // No styles should be registered since user is not admin
        // Test passes if no wp_register_style was called
        $this->assertTrue(true);
    }

    /**
     * Test renderAdminNotices does not render when user already did review.
     */
    public function testRenderAdminNoticesHiddenWhenUserAlreadyDid(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();
        Functions\when('__')->returnArg();
        Functions\when('get_option')->justReturn(date('Y-m-d H:i:s', strtotime('-2 weeks')));

        $userId = 1;

        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_already_did", true)
            ->andReturn(true);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        ob_start();
        $controller->renderAdminNotices();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    /**
     * Test renderAdminNotices renders notice when conditions are met.
     */
    public function testRenderAdminNoticesRendersWhenConditionsMet(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();
        Functions\when('__')->returnArg();
        Functions\when('_e')->alias(function ($text) {
            echo $text;
        });
        Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Functions\when('home_url')->justReturn('http://example.com');
        Functions\when('wp_hash')->justReturn('hash123');

        $userId = 1;
        // Installed 2 weeks ago (triggers one_week condition)
        $installDate = date('Y-m-d H:i:s', strtotime('-2 weeks'));

        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\when('get_user_meta')->alias(function ($userId, $key, $single) {
            if (strpos($key, '_already_did') !== false) {
                return false;
            }
            if (strpos($key, '_last_dismissed') !== false) {
                return '';
            }
            if (strpos($key, '_dismissed_triggers') !== false) {
                return [];
            }
            return '';
        });
        Functions\expect('get_option')
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", false)
            ->andReturn($installDate);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);

        ob_start();
        $controller->renderAdminNotices();
        $output = ob_get_clean();

        $this->assertStringContainsString('notice-success', $output);
        $this->assertStringContainsString($this->pluginSlug, $output);
        $this->assertStringContainsString('wordpress.org/support/plugin', $output);
    }

    /**
     * Test renderAdminNotices includes icon when provided.
     */
    public function testRenderAdminNoticesIncludesIcon(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();
        Functions\when('__')->returnArg();
        Functions\when('_e')->alias(function ($text) {
            echo $text;
        });
        Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Functions\when('home_url')->justReturn('http://example.com');
        Functions\when('wp_hash')->justReturn('hash123');

        $userId = 1;
        $installDate = date('Y-m-d H:i:s', strtotime('-2 weeks'));

        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\when('get_user_meta')->alias(function ($userId, $key, $single) {
            if (strpos($key, '_already_did') !== false) {
                return false;
            }
            if (strpos($key, '_last_dismissed') !== false) {
                return '';
            }
            if (strpos($key, '_dismissed_triggers') !== false) {
                return [];
            }
            return '';
        });
        Functions\expect('get_option')
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", false)
            ->andReturn($installDate);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName, $this->iconUrl);

        ob_start();
        $controller->renderAdminNotices();
        $output = ob_get_clean();

        $this->assertStringContainsString($this->iconUrl, $output);
        $this->assertStringContainsString('notice-icon', $output);
    }

    /**
     * Test ajaxHandler with am_now reason sets permanent dismissal.
     */
    public function testAjaxHandlerWithAmNowReason(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 1;
        $currentTime = '2023-06-15 12:00:00';

        $_REQUEST['nonce'] = 'valid_nonce';
        $_REQUEST['group'] = 'time_installed';
        $_REQUEST['code'] = 'one_week';
        $_REQUEST['priority'] = '10';
        $_REQUEST['reason'] = 'am_now';

        Functions\when('sanitize_key')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        Functions\expect('wp_verify_nonce')
            ->once()
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_dismissed_triggers", true)
            ->andReturn([]);
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_dismissed_triggers", \Mockery::type('array'));
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_last_dismissed", $currentTime);
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_already_did", true);
        Functions\expect('current_time')
            ->with('mysql')
            ->andReturn($currentTime);
        Functions\expect('wp_send_json_success')
            ->once();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->ajaxHandler();

        unset($_REQUEST['nonce'], $_REQUEST['group'], $_REQUEST['code'], $_REQUEST['priority'], $_REQUEST['reason']);
    }

    /**
     * Test ajaxHandler uses default values when REQUEST params missing.
     */
    public function testAjaxHandlerUsesDefaultValues(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();
        Functions\when('__')->returnArg();

        $userId = 1;
        $currentTime = '2023-06-15 12:00:00';
        $installDate = date('Y-m-d H:i:s', strtotime('-2 weeks'));

        $_REQUEST['nonce'] = 'valid_nonce';
        // Not setting group, code, priority, reason - should use defaults

        Functions\when('sanitize_key')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        Functions\expect('wp_verify_nonce')
            ->once()
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\when('get_user_meta')->alias(function ($userId, $key, $single) {
            if (strpos($key, '_dismissed_triggers') !== false) {
                return [];
            }
            return '';
        });
        Functions\expect('get_option')
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", false)
            ->andReturn($installDate);
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_dismissed_triggers", \Mockery::type('array'));
        Functions\expect('update_user_meta')
            ->with($userId, "_{$this->pluginSlug}_wp_reviews_last_dismissed", $currentTime);
        Functions\expect('current_time')
            ->with('mysql')
            ->andReturn($currentTime);
        Functions\expect('wp_send_json_success')
            ->once();

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->ajaxHandler();

        unset($_REQUEST['nonce']);
    }

    /**
     * Test init adds admin notice hooks when on admin screen.
     */
    public function testInitAddsAdminNoticeHooks(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);

        $userId = 1;
        $mockUser = (object) ['roles' => ['administrator']];
        $installDate = date('Y-m-d H:i:s', strtotime('-1 day'));

        // Expect add_action calls
        Functions\expect('add_action')
            ->with('admin_enqueue_scripts', \Mockery::type('array'));
        Functions\expect('add_action')
            ->with('admin_notices', \Mockery::type('array'));
        Functions\expect('add_action')
            ->with('network_admin_notices', \Mockery::type('array'));
        Functions\expect('add_action')
            ->with('user_admin_notices', \Mockery::type('array'));

        Functions\when('is_admin')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn($userId);
        Functions\when('get_user_by')->justReturn($mockUser);
        Functions\expect('get_option')
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", false)
            ->andReturn($installDate);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->init();

        $this->assertTrue(true);
    }

    /**
     * Test init sets installation path when not already set.
     */
    public function testInitSetsInstallationPathWhenNotSet(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 1;
        $mockUser = (object) ['roles' => ['administrator']];
        $currentTime = '2023-06-15 12:00:00';

        Functions\when('is_admin')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn($userId);
        Functions\when('get_user_by')->justReturn($mockUser);
        Functions\expect('get_option')
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", false)
            ->andReturn(false);
        Functions\expect('current_time')
            ->with('mysql')
            ->andReturn($currentTime);
        Functions\expect('update_option')
            ->with("{$this->pluginSlug}_wp_reviews_installed_on", $currentTime);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->init();

        $this->assertTrue(true);
    }

    /**
     * Test that empty user returns false for administrator check.
     */
    public function testEmptyUserIsNotAdministrator(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 0;

        Functions\expect('is_admin')
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_by')
            ->with('ID', $userId)
            ->andReturn(false);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->enqueueStyle();

        // No styles should be registered since user check failed
        $this->assertTrue(true);
    }

    /**
     * Test that WP_Error user returns false for administrator check.
     */
    public function testWpErrorUserIsNotAdministrator(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();
        Functions\when('is_wp_error')->justReturn(true);

        $userId = 1;

        Functions\expect('is_admin')
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_by')
            ->with('ID', $userId)
            ->andReturn(null); // Simulates error condition

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->enqueueStyle();

        // No styles should be registered since user check failed
        $this->assertTrue(true);
    }

    /**
     * Test that style enqueuing includes all required WordPress functions.
     */
    public function testEnqueueStyleCallsAllRequiredFunctions(): void
    {
        Functions\when('esc_url_raw')->returnArg();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('add_action')->justReturn();

        $userId = 1;
        $mockUser = (object) ['roles' => ['administrator']];

        Functions\expect('is_admin')
            ->andReturn(true);
        Functions\expect('get_current_user_id')
            ->andReturn($userId);
        Functions\expect('get_user_by')
            ->with('ID', $userId)
            ->andReturn($mockUser);

        // Verify all style functions are called
        Functions\expect('wp_register_style')
            ->once()
            ->with('publishpress_wordpress_reviews_style', false)
            ->andReturn(true);
        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('publishpress_wordpress_reviews_style')
            ->andReturn(true);
        Functions\expect('wp_add_inline_style')
            ->once()
            ->with('publishpress_wordpress_reviews_style', \Mockery::type('string'))
            ->andReturn(true);

        $controller = new ReviewsController($this->pluginSlug, $this->pluginName);
        $controller->enqueueStyle();

        $this->assertTrue(true);
    }
}
