<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(G5_COMMUNITY_USE === false) {
    include_once(G5_THEME_SHOP_PATH.'/shop.tail.php');
    return;
}
?>
    </div>
</div>

<?php
$willow_nav_script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$willow_is_board_write_page = strpos($willow_nav_script, '/bbs/write.php') !== false;
$willow_is_memo_page = strpos($willow_nav_script, '/bbs/memo.php') !== false;
?>
<?php if (!$willow_is_board_write_page && !$willow_is_memo_page) { ?>
<?php include G5_PATH.'/willow/bottom_nav.inc.php'; ?>
<?php } ?>

<?php
$willow_show_today_prompt = false;
$willow_today_prompt_topic = array();
$willow_today_prompt_url = '';
$willow_entry_paths = array('/index.php', '/willow/splash.php');
$willow_is_entry_page = in_array($willow_nav_script, $willow_entry_paths, true);
$willow_is_get_request = !isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] === 'GET';

if ($is_member && $willow_is_entry_page && $willow_is_get_request) {
    include_once(G5_PATH.'/willow/topic.lib.php');
    include_once(G5_PATH.'/willow/content.lib.php');

    if (function_exists('willow_author_is_escapee') && willow_author_is_escapee($member)) {
        $willow_today_prompt_topic = willow_get_topic();

        if (!empty($willow_today_prompt_topic['wt_id'])) {
            $willow_today_prompt_topic_id = (int) $willow_today_prompt_topic['wt_id'];
            $willow_today_prompt_session_key = 'ss_willow_today_prompt_'.md5($member['mb_id'].'|'.$willow_today_prompt_topic_id);

            if (!get_session($willow_today_prompt_session_key) && !willow_topic_member_has_post($willow_today_prompt_topic_id, $member['mb_id'])) {
                set_session($willow_today_prompt_session_key, G5_SERVER_TIME);
                $willow_show_today_prompt = true;
                $willow_today_prompt_url = willow_topic_write_url($willow_today_prompt_topic);
            }
        }
    }
}
?>
<?php if ($willow_show_today_prompt) { ?>
<div class="willow_today_write_prompt" data-willow-today-write-prompt>
    <div class="willow_today_write_prompt_dim" data-today-prompt-close></div>
    <section class="willow_today_write_prompt_panel" role="dialog" aria-modal="true" aria-labelledby="willow_today_write_prompt_title">
        <button type="button" class="willow_today_write_prompt_close" data-today-prompt-close aria-label="닫기">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </button>
        <span class="willow_today_write_prompt_label">오늘의 글</span>
        <h2 id="willow_today_write_prompt_title"><?php echo get_text($willow_today_prompt_topic['wt_subject']); ?></h2>
        <form class="willow_today_write_prompt_form" action="<?php echo G5_URL; ?>/willow/write_update.php" method="post" autocomplete="off">
            <input type="hidden" name="wt_id" value="<?php echo (int) $willow_today_prompt_topic['wt_id']; ?>">
            <input type="hidden" name="wd_id" value="0">
            <input type="hidden" name="wp_subject" value="<?php echo get_text($willow_today_prompt_topic['wt_subject']); ?>">
            <input type="hidden" name="wp_access" value="public">
            <input type="hidden" name="wp_tags" value="">
            <label for="willow_today_prompt_content">오늘의 주제에 대한 생각을 짧게 남겨보세요.</label>
            <textarea id="willow_today_prompt_content" name="wp_content" maxlength="500" placeholder="당신의 생각을 들려주세요." required></textarea>
            <p><span data-today-prompt-count>0</span>/500자</p>
            <div class="willow_today_write_prompt_actions">
            <button type="button" data-today-prompt-close>나중에</button>
                <button type="submit">완료</button>
            </div>
        </form>
    </section>
</div>
<script>
(function() {
    var prompt = document.querySelector('[data-willow-today-write-prompt]');
    if (!prompt) return;
    var textarea = prompt.querySelector('textarea[name="wp_content"]');
    var count = prompt.querySelector('[data-today-prompt-count]');

    function closePrompt() {
        prompt.classList.add('is_closing');
        window.setTimeout(function() {
            prompt.parentNode && prompt.parentNode.removeChild(prompt);
        }, 180);
    }

    if (textarea && count) {
        textarea.addEventListener('input', function() {
            count.textContent = textarea.value.length;
        });
        window.setTimeout(function() {
            textarea.focus();
        }, 260);
    }

    prompt.querySelectorAll('[data-today-prompt-close]').forEach(function(button) {
        button.addEventListener('click', closePrompt);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.body.contains(prompt)) {
            closePrompt();
        }
    });
})();
</script>
<?php } ?>

<?php
if ($config['cf_analytics']) {
    echo $config['cf_analytics'];
}

include_once(G5_THEME_PATH."/tail.sub.php");
