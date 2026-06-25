<?php
$sub_menu = '700400';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_author_request_install();

$g5['title'] = '작가등록 요청';
$table = willow_author_request_table();
$status = isset($_GET['status']) ? trim($_GET['status']) : 'pending';
$allowed_status = array('pending', 'approved', 'rejected', 'all');
if (!in_array($status, $allowed_status, true)) {
    $status = 'pending';
}

$where = " where 1 ";
if ($status !== 'all') {
    $where .= " and r.war_status = '".sql_escape_string($status)."' ";
}

$requests = array();
$result = sql_query(" select r.*, m.mb_nick, m.mb_name, m.mb_hp, m.mb_email, m.mb_level, m.mb_2
    from `{$table}` r
    left join {$g5['member_table']} m on m.mb_id = r.mb_id
    {$where}
    order by r.war_id desc ", false);

if ($result) {
    while ($row = sql_fetch_array($result)) {
        $requests[] = $row;
    }
}

require_once './admin.head.php';
?>

<style>
.willow_req_status {display:inline-block;padding:4px 8px;border-radius:3px;background:#eee;font-weight:700}
.willow_req_status.pending {background:#fff4d6;color:#a45a00}
.willow_req_status.approved {background:#e6f5ee;color:#06703b}
.willow_req_status.rejected {background:#ffe8e8;color:#c62828}
.willow_req_intro {max-width:360px;line-height:1.45;color:#444}
.willow_req_files a {display:block;margin:2px 0}
.willow_req_actions {display:grid;gap:6px;min-width:160px}
.willow_req_actions textarea {width:100%;height:48px}
.willow_req_filters {display:flex;gap:6px;margin-bottom:10px}
.willow_req_filters a {display:inline-block;padding:7px 10px;border:1px solid #ddd;background:#fff}
.willow_req_filters a.on {background:#333;color:#fff;border-color:#333}
</style>

<div class="local_desc01 local_desc">
    <p>회원의 작가등록 요청을 검토합니다. 승인 시 회원등급은 3, 작가 플래그는 author로 변경되며 작가소개, 카테고리, 정산계좌, 구독금액이 회원정보에 반영됩니다.</p>
</div>

<div class="willow_req_filters">
    <?php foreach (array('pending' => '대기', 'approved' => '승인', 'rejected' => '반려', 'all' => '전체') as $key => $label) { ?>
    <a class="<?php echo $status === $key ? 'on' : ''; ?>" href="./willow_author_request.php?status=<?php echo $key; ?>"><?php echo $label; ?></a>
    <?php } ?>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?></caption>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <th scope="col">상태</th>
            <th scope="col">회원</th>
            <th scope="col">작가유형</th>
            <th scope="col">작가소개</th>
            <th scope="col">카테고리</th>
            <th scope="col">정산/구독</th>
            <th scope="col">첨부</th>
            <th scope="col">요청일</th>
            <th scope="col">처리</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($requests) { ?>
            <?php foreach ($requests as $request) { ?>
            <?php
            $member_name = $request['mb_nick'] ? $request['mb_nick'] : ($request['mb_name'] ? $request['mb_name'] : $request['mb_id']);
            $cert_url = willow_author_request_file_url($request['war_cert_file']);
            $profile_url = willow_author_request_file_url($request['war_profile_image']);
            ?>
            <tr>
                <td class="td_num"><?php echo (int) $request['war_id']; ?></td>
                <td><span class="willow_req_status <?php echo get_text($request['war_status']); ?>"><?php echo get_text($request['war_status']); ?></span></td>
                <td>
                    <strong><?php echo get_text($member_name); ?></strong><br>
                    <span><?php echo get_text($request['mb_id']); ?></span><br>
                    <span>Lv.<?php echo (int) $request['mb_level']; ?> <?php echo get_text($request['mb_2']); ?></span>
                </td>
                <td><?php echo $request['war_is_escapee'] === 'yes' ? '탈북이주민 작가' : '일반작가'; ?></td>
                <td class="willow_req_intro"><?php echo nl2br(get_text(cut_str($request['war_intro'], 140, '...'))); ?></td>
                <td><?php echo get_text($request['war_categories']); ?></td>
                <td>
                    <div><?php echo get_text($request['war_bank_name']); ?> / <?php echo get_text($request['war_account_holder']); ?></div>
                    <div><?php echo get_text($request['war_account_number']); ?></div>
                    <strong><?php echo number_format((int) $request['war_subscribe_price']); ?>원</strong>
                </td>
                <td class="willow_req_files">
                    <?php if ($profile_url) { ?><a href="<?php echo $profile_url; ?>" target="_blank" rel="noopener">프로필 이미지</a><?php } ?>
                    <?php if ($cert_url) { ?><a href="<?php echo $cert_url; ?>" target="_blank" rel="noopener">확인증 파일</a><?php } ?>
                </td>
                <td class="td_datetime"><?php echo get_text($request['war_datetime']); ?></td>
                <td>
                    <form class="willow_req_actions" method="post" action="./willow_author_request_update.php">
                        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                        <input type="hidden" name="war_id" value="<?php echo (int) $request['war_id']; ?>">
                        <textarea name="admin_memo" placeholder="처리 메모"><?php echo get_text($request['war_admin_memo'], 0); ?></textarea>
                        <?php if ($request['war_status'] === 'pending') { ?>
                        <button type="submit" name="action" value="approve" class="btn btn_01">승인</button>
                        <button type="submit" name="action" value="reject" class="btn btn_02">반려</button>
                        <?php } else { ?>
                        <button type="submit" name="action" value="memo" class="btn btn_03">메모저장</button>
                        <?php } ?>
                    </form>
                </td>
            </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td colspan="10" class="empty_table">작가등록 요청이 없습니다.</td></tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
require_once './admin.tail.php';
