<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('logs', 'LogsController::index');

// ──────────────────────────────────────────────
//  API v1 — Public (no token needed)
// ──────────────────────────────────────────────
$routes->group('api/auth', ['namespace' => 'App\Controllers\Api\Auth'], function ($routes) {
    $routes->post('login',           'AuthController::login');
    $routes->post('register',        'RegisterController::register');
    $routes->post('change-password', 'ChangePasswordController::change');
});

$routes->group('api/user', ['namespace' => 'App\Controllers\Api\User'], function ($routes) {
    $routes->post('update-profile',    'UpdateProfileController::update');
    $routes->post('2fa',               'TwoFactorController::toggle');

    // Online / Offline status
    $routes->post('online',            'OnlineStatusController::online');
    $routes->post('offline',           'OnlineStatusController::offline');
    $routes->get('(:num)/status',      'OnlineStatusController::status/$1');

    // Block / Unblock
    $routes->post('block/(:num)',           'BlockController::block/$1');
    $routes->post('unblock/(:num)',         'BlockController::unblock/$1');
    $routes->get('blocked',                 'BlockController::blocked');
    $routes->get('(:num)/block-status',     'BlockController::blockStatus/$1');
});

// ── Contacts ──────────────────────────────────
$routes->group('api/contacts', ['namespace' => 'App\Controllers\Api\User'], function ($routes) {
    $routes->post('add',               'ContactController::add');
    $routes->get('/',                  'ContactController::index');
    $routes->get('(:num)/profile',     'ContactController::profile/$1');
    $routes->put('(:num)',             'ContactController::edit/$1');
    $routes->delete('(:num)',          'ContactController::remove/$1');
});

// ── My Profile ────────────────────────────────
$routes->get('api/user/me',      '\App\Controllers\Api\User\MyProfileController::me');
$routes->get('api/user/(:num)', '\App\Controllers\Api\User\MyProfileController::show/$1');
$routes->post('api/auth/logout', '\App\Controllers\Api\Auth\LogoutController::logout');

// ── Groups ────────────────────────────────────
$routes->group('api/groups', ['namespace' => 'App\Controllers\Api\Group'], function ($routes) {
    $routes->post('/',                              'GroupController::create');
    $routes->get('/',                               'GroupController::myGroups');
    $routes->get('(:num)',                          'GroupController::show/$1');
    $routes->put('(:num)',                          'GroupController::update/$1');
    $routes->delete('(:num)',                       'GroupController::delete/$1');
    $routes->post('(:num)/members',                 'GroupController::addMember/$1');
    $routes->delete('(:num)/members/(:num)',        'GroupController::removeMember/$1/$2');
    $routes->post('(:num)/leave',                   'GroupController::leave/$1');
    $routes->post('(:num)/messages/send',           'GroupMessageController::send/$1');
    $routes->get('(:num)/messages',                 'GroupMessageController::messages/$1');
    $routes->delete('(:num)/messages/(:num)',       'GroupMessageController::deleteMessage/$1/$2');
    $routes->post('(:num)/messages/(:num)/reply',   'GroupMessageController::reply/$1/$2');
    $routes->post('(:num)/messages/(:num)/react',   'GroupMessageController::react/$1/$2');
    $routes->delete('(:num)/messages/(:num)/react', 'GroupMessageController::removeReact/$1/$2');
    $routes->get('(:num)/messages/(:num)/reactions','GroupMessageController::reactions/$1/$2');
    $routes->post('(:num)/messages/mark-read',      'GroupMessageController::markRead/$1');
    $routes->get('(:num)/messages/(:num)/seen-by',  'GroupMessageController::seenBy/$1/$2');
    $routes->get('(:num)/messages/images',          'GroupMessageController::images/$1');
    $routes->get('(:num)/messages/videos',          'GroupMessageController::videos/$1');
    $routes->get('(:num)/messages/voices',          'GroupMessageController::voices/$1');
    $routes->get('(:num)/messages/documents',       'GroupMessageController::documents/$1');
    $routes->get('(:num)/messages/replies',         'GroupMessageController::replies/$1');
});

// ── Messaging ─────────────────────────────────
$routes->group('api/messages', ['namespace' => 'App\Controllers\Api\Message'], function ($routes) {

    // 13. Send message (text or file)
    $routes->post('send',                  'MessageController::send');

    // 14. Get conversation with a user
    $routes->get('(:num)',                 'MessageController::conversation/$1');

    // 14b. Inbox (last message per contact)
    $routes->get('inbox',                  'MessageController::inbox');

    // 15. Delete message
    $routes->delete('(:num)',              'MessageController::delete/$1');

    // 16. React to a message
    $routes->post('(:num)/react',          'MessageController::react/$1');
    $routes->delete('(:num)/react',        'MessageController::removeReact/$1');

    // 16c. Reply to a message
    $routes->post('(:num)/reply',          'MessageController::reply/$1');

    // 17. Mark as read (✓✓ seen)
    $routes->post('read',                  'MessageController::markRead');

    // 17b. Mark as delivered (✓ delivered)
    $routes->post('delivered',             'MessageController::markDelivered');

    // 17c. Unread count
    $routes->get('unread-count',           'MessageController::unreadCount');

    // 18. Reactions for a specific message
    $routes->get('(:num)/reactions',       'MessageController::reactions/$1');

    // 18b. Online/offline status of a user
    $routes->get('(:num)/status',          'MessageController::userStatus/$1');

    // 19. Media filters — shared conversation with a user
    $routes->get('(:num)/images',          'MessageController::images/$1');
    $routes->get('(:num)/videos',          'MessageController::videos/$1');
    $routes->get('(:num)/voices',          'MessageController::voices/$1');
    $routes->get('(:num)/audio',           'MessageController::audio/$1');
    $routes->get('(:num)/documents',       'MessageController::documents/$1');

    // 20. Replies in a conversation
    $routes->get('(:num)/replies',         'MessageController::replies/$1');
});

// ──────────────────────────────────────────────
//  Notifications API
// ──────────────────────────────────────────────
$routes->group('api/notifications', ['namespace' => 'App\Controllers\Api\Notification'], function ($routes) {
    $routes->get('',               'NotificationController::index');
    $routes->get('unread-count',   'NotificationController::unreadCount');
    $routes->post('read-all',      'NotificationController::readAll');
    $routes->post('send',          'NotificationController::send');
    $routes->post('(:num)/read',   'NotificationController::markRead/$1');
});

// ──────────────────────────────────────────────
//  Admin Panel
// ──────────────────────────────────────────────
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function ($routes) {
    $routes->get('/',        'DashboardController::index');
    $routes->get('login',    'AdminAuthController::login');
    $routes->post('login',   'AdminAuthController::login');
    $routes->get('logout',   'AdminAuthController::logout');

    // Users
    $routes->get('users',                          'UsersAdminController::index');
    $routes->get('users/create',                   'UsersAdminController::create');
    $routes->post('users/create',                  'UsersAdminController::create');
    $routes->get('users/(:num)',                   'UsersAdminController::show/$1');
    $routes->post('users/(:num)/suspend',          'UsersAdminController::suspend/$1');
    $routes->post('users/(:num)/activate',         'UsersAdminController::activate/$1');
    $routes->post('users/(:num)/delete',           'UsersAdminController::delete/$1');
    $routes->post('users/(:num)/reset-password',   'UsersAdminController::resetPassword/$1');

    // Groups
    $routes->get('groups',                              'GroupsAdminController::index');
    $routes->get('groups/create',                       'GroupsAdminController::create');
    $routes->post('groups/create',                      'GroupsAdminController::create');
    $routes->get('groups/(:num)',                       'GroupsAdminController::show/$1');
    $routes->post('groups/(:num)/delete',               'GroupsAdminController::delete/$1');
    $routes->post('groups/(:num)/members/add',          'GroupsAdminController::addMember/$1');
    $routes->post('groups/(:num)/members/(:num)/remove','GroupsAdminController::removeMember/$1/$2');

    // Messages viewer
    $routes->get('messages',                  'MessagesAdminController::index');
    $routes->get('messages/conversation',     'MessagesAdminController::conversation');
    $routes->get('messages/group',            'MessagesAdminController::group');

    // Notifications
    $routes->get('notifications',             'NotificationsAdminController::index');
    $routes->post('notifications/send',       'NotificationsAdminController::send');
    $routes->get('notifications/(:num)',      'NotificationsAdminController::show/$1');

    // Logs
    $routes->get('logs', 'LogsAdminController::index');
});
