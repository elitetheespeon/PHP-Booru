<?php
//All routing goes in here
//Main page
$f3->route('GET|POST /','post->main_page');

//Routes for post pages
$f3->route(array(
    'GET|POST /post/list',
    'GET|POST /post/list/@tags',
    'GET|POST /post/list/@tags/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','post_list');
    	include 'includes/post/post_list.php';
    	$template=new Template;
        echo $template->render('post_list.html');
    }
);

$f3->route(array(
    'GET|POST /post/all',
    'GET|POST /post/all/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','post_all');
    	include 'includes/post/post_all.php';
    	$template=new Template;
        echo $template->render('post_list.html');
    }
);

$f3->route(array(
    'GET|POST /post/view/@id',
    'GET|POST /post/view/@id/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','post_view');
    	include 'includes/post/post_view.php';
    	$template=new Template;
        echo $template->render('post_view.html');
    }
);

$f3->route('GET|POST /post/add',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','post_add');
    	include 'includes/post/post_add.php';
    	$template=new Template;
        echo $template->render('post_add.html');
    }
);

$f3->route('GET|POST /post/random',
    function ($f3) {
    	global $db;
    	$post = new post();
    	$randomid = $post->get_random();
    	if ($randomid == ""){
    	    $f3->reroute('/post/random');
    	}else{
    	    $f3->reroute('/post/view/'.$randomid);
    	}
    }
);

$f3->route('GET|POST /post/3d',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','post_3d');
    	include 'includes/post/post_3d.php';
    	$template=new Template;
        echo $template->render('post_3d.html');
    }
);

$f3->route('GET /post/vote/@id/@type',
    function ($f3) {
    	global $db;
    	include 'includes/post/post_vote.php';
    }
);

$f3->route('POST /post/approve','post->approve');

$f3->route('POST /post/edit','post->edit_post');

$f3->route('POST /post/softdelete','post->soft_delete');

$f3->route('POST /post/softrestore','post->soft_restore');

$f3->route('POST /post/changestatus/@id','post->change_status');

$f3->route('POST /post/changednp/@id','post->change_dnp');

$f3->route('POST /post/edit/autocomplete','tag->autocomplete');

$f3->route('POST /post/search/autocomplete','search->autocomplete');

$f3->route('POST /search',
    function ($f3) {
    	$f3->reroute('/post/list/'.str_replace('..', '', urlencode($f3->get('POST.tags'))));
    }
);

//Routes for pool pages
$f3->route(array(
    'GET|POST /pool/view/@id',
    'GET|POST /pool/view/@id/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','pool_view');
    	include 'includes/pool/pool_view.php';
    	$template=new Template;
        echo $template->render('pool_view.html');
    }
);

$f3->route('GET|POST /pool/edit/@mode','post->edit_pool');

$f3->route('GET|POST /pool/edit_options/@id',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','pool_edit');
    	include 'includes/pool/pool_edit.php';
    	$template=new Template;
        echo $template->render('pool_edit.html');
    }
);

$f3->route(array(
    'GET|POST /pool/editor/@id',
    'GET|POST /pool/editor/@id/@page',
    'GET|POST /pool/editor/@id/@page/@limit'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','pool_editor');
    	include 'includes/pool/pool_editor.php';
    	$template=new Template;
        echo $template->render('pool_editor.html');
    }
);

$f3->route(array(
    'GET|POST /pool/list',
    'GET|POST /pool/list/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','pool_list');
    	include 'includes/pool/pool_list.php';
    	$template=new Template;
        echo $template->render('pool_list.html');
    }
);

$f3->route('GET|POST /pool/add',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','pool_add');
    	include 'includes/pool/pool_add.php';
    	$template=new Template;
        echo $template->render('pool_add.html');
    }
);

$f3->route('POST /pool/addpost','post->add_to_pool');

//Routes for user pages
$f3->route('GET|POST /user/login/00',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','login');
    	include 'includes/user/login.php';
    	$template=new Template;
        echo $template->render('login.html');
    }
);

$f3->route('GET /user/login/01','user->logout');

$f3->route('GET|POST /user/register',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','register');
    	include 'includes/user/register.php';
    	$template=new Template;
        echo $template->render('register.html');
    }
);
$f3->route(array(
    'GET|POST /user/resetpass',
    'GET|POST /user/resetpass/@code/@id'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','reset_password');
    	include 'includes/user/reset_password.php';
    	$template=new Template;
        echo $template->render('reset_password.html');
    }
);

$f3->route('GET|POST /user/home',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','user_home');
    	include 'includes/user/user_home.php';
    	$template=new Template;
        echo $template->render('user_home.html');
    }
);

$f3->route('GET|POST /user/options',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','user_options');
    	include 'includes/user/user_options.php';
    	$template=new Template;
        echo $template->render('user_options.html');
    }
);

$f3->route('GET|POST /user/avatar',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','user_avatar');
    	include 'includes/user/user_avatar.php';
    	$template=new Template;
        echo $template->render('user_avatar.html');
    }
);

$f3->route('GET|POST /user/changepass',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','user_changepass');
    	include 'includes/user/change_password.php';
    	$template=new Template;
        echo $template->render('change_password.html');
    }
);

$f3->route(array(
    'GET /user/@id',
    'GET /user/name/@uname'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','user_profile');
    	include 'includes/user/user_profile.php';
    	$template=new Template;
        echo $template->render('user_profile.html');
    }
);

//Routes for history
$f3->route(array(
    'GET|POST /history/@type',
    'GET|POST /history/@type/@id',
    'GET|POST /history/note/@id/@pid',
    'GET|POST /history/@type/@id/@version',
    'GET|POST /history/@type/@id/@pid/@version'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','history');
    	include 'includes/history.php';
    }
);

//Routes for dmail
$f3->route('POST /dmail/autocomplete','dmail->autocomplete');

$f3->redirect('GET /mail','/mail/inbox/');

$f3->route('GET|POST /mail/view/@id',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','mail_view');
    	include 'includes/mail/mail_view.php';
    	$template=new Template;
        echo $template->render('mail_view.html');
    }
);

$f3->route(array(
    'GET /mail/inbox',
    'GET /mail/inbox/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','mail_inbox');
    	include 'includes/mail/mail_inbox.php';
    	$template=new Template;
        echo $template->render('mail_inbox.html');
    }
);

$f3->route(array(
    'GET /mail/outbox',
    'GET /mail/outbox/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','mail_outbox');
    	include 'includes/mail/mail_outbox.php';
    	$template=new Template;
        echo $template->render('mail_outbox.html');
    }
);

$f3->route(array(
    'GET|POST /mail/send',
    'GET|POST /mail/send/@id'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','mail_send');
    	include 'includes/mail/mail_send.php';
    	$template=new Template;
        echo $template->render('mail_send.html');
    }
);

$f3->route(array(
    'GET|POST /mail/conv_view/@id',
    'GET|POST /mail/conv_view/@id/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','mail_conv_view');
    	include 'includes/mail/mail_conv_view.php';
    	$template=new Template;
        echo $template->render('mail_conv_view.html');
    }
);

$f3->route(array(
    'GET|POST /mail/conv_list',
    'GET|POST /mail/conv_list/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','mail_conv_list');
    	include 'includes/mail/mail_conv_list.php';
    	$template=new Template;
        echo $template->render('mail_conv_list.html');
    }
);

//Routes for forums
$f3->route(array(
    'GET|POST /forum/view/@id',
    'GET|POST /forum/view/@id/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','forum_view');
    	include 'includes/forum/forum_view.php';
    	$template=new Template;
        echo $template->render('forum_view.html');
    }
);

$f3->route(array(
    'GET|POST /forum/list',
    'GET|POST /forum/list/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','forum_list');
    	include 'includes/forum/forum_list.php';
    	$template=new Template;
        echo $template->render('forum_list.html');
    }
);

$f3->route(array(
    'GET|POST /forum/add',
    'GET|POST /forum/add/@type',
    'GET|POST /forum/add/@type/@id'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','forum_add');
    	include 'includes/forum/forum_add.php';
    	$template=new Template;
        echo $template->render('forum_add.html');
    }
);

$f3->route(array(
    'GET|POST /forum/edit/@option/@topicid/@page',
    'GET|POST /forum/edit/@option/@topicid/@postid/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','forum_edit');
    	include 'includes/forum/forum_edit.php';
    }
);

$f3->route('GET|POST /forum/remove/@type/@id/@page',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','forum_remove');
    	include 'includes/forum/forum_remove.php';
    }
);

//Routes for RSS feeds
$f3->route('GET /atom',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','atom_feed');
    	include 'includes/atom.php';
    	$template=new Template;
        echo $template->render('atom.xml','application/xml');
    }
);

$f3->route(array(
    'GET|POST /cooliris',
    'GET|POST /cooliris/@query',
    'GET|POST /cooliris/@query/@next'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','cooliris_feed');
    	include 'includes/cooliris.php';
    	$template=new Template;
        echo $template->render('cooliris.xml','application/xml');
    }
);

//Routes for comments
$f3->route('GET|POST /comment/edit/@id','comment->edit');

$f3->route('GET|POST /comment/add/@id','comment->add');

$f3->route('GET|POST /comment/vote/@id/@cid/@vote','comment->vote');

$f3->route(array(
    'GET|POST /comment/list',
    'GET|POST /comment/list/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','comment_list');
    	include 'includes/comment_list.php';
    	$template=new Template;
        echo $template->render('comment_list.html');
    }
);

//Routes for favorites
$f3->route(array(
    'GET|POST /favorites/view/@id',
    'GET|POST /favorites/view/@id/@page'),
    'favorites->fav_view');
    
$f3->route(array(
    'GET|POST /favorites/list',
    'GET|POST /favorites/list/@page'),
    'favorites->fav_list');
    
$f3->route('GET|POST /favorites/delete/@id/@page','favorites->fav_delete');

$f3->route('GET|POST /favorites/add/@id','favorites->fav_add');

//Routes for tag alias
$f3->route(array(
    'GET|POST /tag/list',
    'GET|POST /tag/list/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','tag_alias');
    	include 'includes/alias.php';
    	$template=new Template;
        echo $template->render('tag_alias.html');
    }
);

//Routes for help
$f3->route('GET /help',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','help_home');
    	$template=new Template;
        echo $template->render('help_home.html');
    }
);

$f3->route('GET /help/posts',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','help_posts');
    	$template=new Template;
        echo $template->render('help_posts.html');
    }
);

$f3->route('GET /help/pools',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','help_pools');
    	$template=new Template;
        echo $template->render('help_pools.html');
    }
);

$f3->route('GET /help/ratings',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','help_ratings');
    	$template=new Template;
        echo $template->render('help_ratings.html');
    }
);

$f3->route('GET /help/forum',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','help_forum');
    	$template=new Template;
        echo $template->render('help_forum.html');
    }
);

$f3->route('GET /help/wiki',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','help_wiki');
    	$template=new Template;
        echo $template->render('help_wiki.html');
    }
);

//Routes for admin panel
$f3->route('GET /admin',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_home');
    	$template=new Template;
        include 'includes/admin/home.php';
        echo $template->render('admin_home.html');
    }
);

$f3->route('GET|POST /admin/add_group',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_add_group');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/add_group.php';
        echo $template->render('admin_add_group.html');
    }
);

$f3->route(array(
    'GET|POST /admin/edit_group',
    'GET|POST /admin/edit_group/@delete'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_edit_group');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/edit_group.php';
        echo $template->render('admin_edit_group.html');
    }
);

$f3->route(array(
    'GET|POST /admin/alias',
    'GET|POST /admin/alias/@alias/@tag'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_alias');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/alias.php';
        echo $template->render('admin_alias.html');
    }
);

$f3->route(array(
    'GET|POST /admin/ban_user',
    'GET|POST /admin/ban_user/@id'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_ban_user');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/ban_user.php';
        echo $template->render('admin_ban_user.html');
    }
);

$f3->route(array(
    'GET|POST /admin/reported_comments',
    'GET|POST /admin/reported_comments/@page',
    'GET|POST /admin/reported_comments/@type/@reportid',
    'GET|POST /admin/reported_comments/@type/@reportid/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_report_comments');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/comments.php';
        echo $template->render('admin_comments.html');
    }
);

$f3->route(array(
    'GET|POST /admin/reported_posts',
    'GET|POST /admin/reported_posts/@page',
    'GET|POST /admin/reported_posts/@type/@reportid',
    'GET|POST /admin/reported_posts/@type/@reportid/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_report_posts');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/posts.php';
        echo $template->render('admin_posts.html');
    }
);

$f3->route('GET|POST /admin/edit_user',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_edit_user');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/edit_user.php';
        echo $template->render('admin_edit_user.html');
    }
);

$f3->route('GET|POST /admin/options',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_options');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/options.php';
        echo $template->render('admin_options.html');
    }
);

$f3->route(array(
    'GET|POST /admin/mail_list',
    'GET|POST /admin/mail_list/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_maillist');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/mail_list.php';
        echo $template->render('admin_mail_list.html');
    }
);

$f3->route('POST /admin/mail_list/autocomplete','dmail->admin_autocomplete');

$f3->route('GET|POST /admin/mail_view/@id',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_mailview');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/mail_view.php';
        echo $template->render('admin_mail_view.html');
    }
);

$f3->route('GET|POST /admin/log_view/autocomplete',
    function ($f3) {
    	global $db;
        include 'includes/admin/view_log.autocmp.php';
    }
);

$f3->route(array(
    'GET|POST /admin/log_view',
    'GET|POST /admin/log_view/@page'),
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_log_view');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/view_log.php';
        echo $template->render('admin_view_log.html');
    }
);

$f3->route('GET|POST /admin/mass_parent',
    function ($f3) {
    	global $db;
    	$f3->set('pagename','admin_mass_parent');
    	$template=new Template;
        $user=new user;
        include 'includes/admin/mass_parent.php';
        echo $template->render('admin_mass_parent.html');
    }
);

//stats page routes
$f3->route('GET|POST /stats', 'stats->render');
$f3->route('GET|POST /stats/@page', 'stats->render');
$f3->route('GET|POST /stats/@sort/@page', 'stats->render');

//Routes for wiki
$f3->route(array(
    'GET|POST /wiki/list',
    'GET|POST /wiki/list/@page'),
    'wiki->list_all');

$f3->route(array(
    'GET|POST /wiki/view',
    'GET|POST /wiki/view/@id',
    'GET|POST /wiki/view/@id/@version'),
    'wiki->view');

$f3->route('GET /wiki/new', 'wiki->create');

$f3->route('GET /wiki/new/@title', 'wiki->create');

$f3->route('POST /wiki/new/@title', 'wiki->save');

$f3->route('POST /wiki/new', 'wiki->save');

$f3->route('GET /wiki/compare/@id/@before_id/@after_id', 'wiki->compare');

$f3->route('POST /wiki/compare/@id', 'wiki->compare_lookup');

$f3->route('GET /wiki/history/@id', 'wiki->version_history');

$f3->route('GET /wiki/history/revert/@id', 'wiki->revert_history');

$f3->route('POST /wiki/revert/@id', 'wiki->revert');

$f3->route('POST /wiki/edit/@id', 'wiki->save');

$f3->route('GET /wiki/edit/@id', 'wiki->edit');

$f3->route('GET /wiki/unlock/@id', 'wiki->unlock');

$f3->route('GET /wiki/lock/@id', 'wiki->lock');

$f3->route('GET /wiki/delete/@id', 'wiki->remove');

$f3->route('GET /wiki/title/@title', 'wiki->view_title');

//Random
$f3->route('GET|POST /note/save/@id','post->note_save');

$f3->route('GET|POST /report/@type/@rid','misc->report');

$f3->route(array(
    'GET|POST /remove/@type/@id',
    'GET|POST /remove/@type/@id/@altid'),
    'misc->remove');

$f3->route('GET /terms',
    function ($f3) {
    	$template=new Template;
        echo $template->render('terms_of_service.html');
    }
);

$f3->route('GET /chat',
    function ($f3) {
    	$user = new user();
    	$f3->set('user',$user);
    	$template=new Template;
        echo $template->render('chat.html');
    }
);

//API
$f3->route('POST /api/search','api->search');

$f3->route('POST /api/search/multi','api->search');

$f3->route('POST /api/search/all','api->search_all');

//Redirect all 404s
//$f3->set('ONERROR','post->main_page');
?>