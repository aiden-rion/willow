<?php
$sub_menu = '700800';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_author_metric_install();

$g5['title'] = '작가추천 평가지표';
$table = willow_author_metric_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_demo();
    auth_check_menu($auth, $sub_menu, 'w');
    check_admin_token();

    $recent_days = max(1, (int) $_POST['wam_recent_days']);
    $like_weight = max(0, (float) $_POST['wam_like_weight']);
    $comment_weight = max(0, (float) $_POST['wam_comment_weight']);
    $post_weight = max(0, (float) $_POST['wam_post_weight']);
    $subscriber_weight = max(0, (float) $_POST['wam_subscriber_weight']);
    $view_weight = max(0, (float) $_POST['wam_view_weight']);
    $display_limit = max(1, (int) $_POST['wam_display_limit']);
    $candidate_limit = max($display_limit, (int) $_POST['wam_candidate_limit']);

    sql_query(" update `{$table}`
        set wam_recent_days = '{$recent_days}',
            wam_like_weight = '".sql_escape_string($like_weight)."',
            wam_comment_weight = '".sql_escape_string($comment_weight)."',
            wam_post_weight = '".sql_escape_string($post_weight)."',
            wam_subscriber_weight = '".sql_escape_string($subscriber_weight)."',
            wam_view_weight = '".sql_escape_string($view_weight)."',
            wam_display_limit = '{$display_limit}',
            wam_candidate_limit = '{$candidate_limit}',
            wam_update_datetime = '".G5_TIME_YMDHIS."'
        where wam_id = '1' ");

    goto_url('./willow_author_metric.php');
}

$config = willow_author_metric_config();
$preview_authors = willow_get_recommended_authors((int) $config['wam_display_limit'], (int) $config['wam_recent_days']);

require_once './admin.head.php';
?>

<form name="fwillowauthormetric" method="post" action="./willow_author_metric.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

    <div class="local_desc01 local_desc">
        <p>메인 작가추천 영역의 내부 평가점수를 관리합니다. 사용자 화면에는 점수가 노출되지 않고 추천 순서 산정에만 사용됩니다.</p>
        <p>평가점수 = 댓글수×댓글 가중치 + 좋아요수×좋아요 가중치 + 최근글수×글 가중치 + 구독자수×구독자 가중치 + 조회수×조회수 가중치</p>
    </div>

    <section class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 설정</caption>
            <tbody>
            <tr>
                <th scope="row"><label for="wam_recent_days">최근 집계 기간</label></th>
                <td><input type="number" id="wam_recent_days" name="wam_recent_days" value="<?php echo (int) $config['wam_recent_days']; ?>" class="frm_input" min="1" style="width:90px"> 일</td>
            </tr>
            <tr>
                <th scope="row"><label for="wam_comment_weight">댓글 가중치</label></th>
                <td><input type="number" step="0.1" id="wam_comment_weight" name="wam_comment_weight" value="<?php echo get_text($config['wam_comment_weight']); ?>" class="frm_input" min="0" style="width:90px"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wam_like_weight">좋아요 가중치</label></th>
                <td><input type="number" step="0.1" id="wam_like_weight" name="wam_like_weight" value="<?php echo get_text($config['wam_like_weight']); ?>" class="frm_input" min="0" style="width:90px"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wam_post_weight">최근글 가중치</label></th>
                <td><input type="number" step="0.1" id="wam_post_weight" name="wam_post_weight" value="<?php echo get_text($config['wam_post_weight']); ?>" class="frm_input" min="0" style="width:90px"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wam_subscriber_weight">구독자 가중치</label></th>
                <td><input type="number" step="0.1" id="wam_subscriber_weight" name="wam_subscriber_weight" value="<?php echo get_text($config['wam_subscriber_weight']); ?>" class="frm_input" min="0" style="width:90px"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wam_view_weight">조회수 가중치</label></th>
                <td><input type="number" step="0.01" id="wam_view_weight" name="wam_view_weight" value="<?php echo get_text($config['wam_view_weight']); ?>" class="frm_input" min="0" style="width:90px"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wam_display_limit">메인 노출 작가 수</label></th>
                <td><input type="number" id="wam_display_limit" name="wam_display_limit" value="<?php echo (int) $config['wam_display_limit']; ?>" class="frm_input" min="1" style="width:90px"> 명</td>
            </tr>
            <tr>
                <th scope="row"><label for="wam_candidate_limit">평가 후보 작가 수</label></th>
                <td><input type="number" id="wam_candidate_limit" name="wam_candidate_limit" value="<?php echo (int) $config['wam_candidate_limit']; ?>" class="frm_input" min="1" style="width:90px"> 명</td>
            </tr>
            </tbody>
        </table>
    </section>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
    </div>
</form>

<section class="tbl_head01 tbl_wrap">
    <h2>현재 추천 결과 미리보기</h2>
    <table>
        <caption>작가추천 평가 결과</caption>
        <thead>
        <tr>
            <th scope="col">순위</th>
            <th scope="col">작가</th>
            <th scope="col">내부점수</th>
            <th scope="col">좋아요</th>
            <th scope="col">댓글</th>
            <th scope="col">최근글</th>
            <th scope="col">구독자</th>
            <th scope="col">조회수</th>
            <th scope="col">최근작성일</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($preview_authors) { ?>
        <?php foreach ($preview_authors as $index => $author) { ?>
        <tr>
            <td class="td_num"><?php echo $index + 1; ?></td>
            <td><a href="<?php echo G5_ADMIN_URL; ?>/member_form.php?w=u&amp;mb_id=<?php echo urlencode($author['id']); ?>"><?php echo $author['name']; ?></a></td>
            <td class="td_num"><?php echo number_format($author['score'], 2); ?></td>
            <td class="td_num"><?php echo number_format($author['likes']); ?></td>
            <td class="td_num"><?php echo number_format($author['comments']); ?></td>
            <td class="td_num"><?php echo number_format($author['post_count']); ?></td>
            <td class="td_num"><?php echo number_format($author['subscriber_count']); ?></td>
            <td class="td_num"><?php echo number_format($author['views']); ?></td>
            <td><?php echo get_text($author['recent_datetime']); ?></td>
        </tr>
        <?php } ?>
        <?php } else { ?>
        <tr><td colspan="9" class="empty_table">추천할 작가 데이터가 없습니다.</td></tr>
        <?php } ?>
        </tbody>
    </table>
</section>

<?php
require_once './admin.tail.php';
