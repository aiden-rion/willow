<?php
$sub_menu = '700300';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_banner_install();

$g5['title'] = '배너관리';
$table = willow_banner_table();
$positions = willow_banner_positions();
$banners = array();
$result = sql_query(" select * from `{$table}` order by wb_position asc, wb_sort asc, wb_id desc ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $banners[] = $row;
    }
}

require_once './admin.head.php';
?>

<style>
.willow_banner_add_card {margin:14px 0 24px;padding:16px;border:1px solid #d9d9d9;background:#fff}
.willow_banner_add_card h3 {margin:0 0 14px;font-size:15px}
.willow_banner_add_form {display:grid;grid-template-columns:160px 1fr 1fr 1fr 100px;gap:12px;align-items:end}
.willow_banner_add_card label {display:block;margin:9px 0 5px;font-weight:700}
.willow_banner_add_card .frm_input,
.willow_banner_add_card select {width:100%;height:36px}
.willow_banner_add_card .btn_submit {margin-top:12px}
.willow_banner_add_checks {display:flex;gap:14px;align-items:center;margin-top:12px}
.willow_banner_preview img {max-width:180px;max-height:70px;border:1px solid #ddd}
@media (max-width:1100px){.willow_banner_add_form{grid-template-columns:1fr 1fr}}
@media (max-width:720px){.willow_banner_add_form{grid-template-columns:1fr}}
</style>

<div class="local_desc01 local_desc">
    <p>배너영역은 메인, 게시글상세 가장 하단, 메뉴페이지 가장 하단 3개 영역으로 관리합니다.</p>
    <p>아래 신규 추가 영역에서 배너를 추가하고, 목록에서는 기존에 등록된 배너만 확인 및 수정할 수 있습니다. 이미지는 JPG, PNG, GIF, WebP 형식, 파일당 최대 5MB까지 업로드할 수 있습니다.</p>
</div>

<form name="fwillowbanneradd" method="post" action="./willow_banner_update.php" enctype="multipart/form-data">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="action_mode" value="add">

    <section class="willow_banner_add_card" aria-label="신규 배너 추가">
        <h3>신규 배너 추가</h3>
        <div class="willow_banner_add_form">
            <div>
                <label for="new_wb_position">배너영역</label>
                <select id="new_wb_position" name="new_wb_position[]">
                    <?php foreach ($positions as $key => $label) { ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label for="new_wb_title">관리용 제목</label>
                <input type="text" id="new_wb_title" name="new_wb_title[]" value="" class="frm_input" maxlength="255">
            </div>
            <div>
                <label for="new_wb_alt">이미지 설명</label>
                <input type="text" id="new_wb_alt" name="new_wb_alt[]" value="" class="frm_input" maxlength="255">
            </div>
            <div>
                <label for="new_wb_url">링크</label>
                <input type="text" id="new_wb_url" name="new_wb_url[]" value="" class="frm_input" maxlength="255" placeholder="https:// 또는 /willow/...">
            </div>
            <div>
                <label for="new_wb_sort">정렬</label>
                <input type="number" id="new_wb_sort" name="new_wb_sort[]" value="100" class="frm_input">
            </div>
            <div>
                <label for="new_wb_image">이미지</label>
                <input type="file" id="new_wb_image" name="new_wb_image[]" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
        </div>
        <div class="willow_banner_add_checks">
            <label><input type="checkbox" name="new_wb_new_win[0]" value="1"> 새창</label>
            <label><input type="checkbox" name="new_wb_active[0]" value="1" checked> 노출</label>
        </div>
    </section>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="신규 배너 추가" class="btn_submit btn">
    </div>
</form>

<form name="fwillowbanner" method="post" action="./willow_banner_update.php" enctype="multipart/form-data">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="action_mode" value="update">

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 목록</caption>
            <thead>
            <tr>
                <th scope="col">영역</th>
                <th scope="col">정렬</th>
                <th scope="col">제목/대체텍스트</th>
                <th scope="col">링크</th>
                <th scope="col">이미지</th>
                <th scope="col">새창</th>
                <th scope="col">노출</th>
                <th scope="col">삭제</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($banners) { ?>
            <?php foreach ($banners as $banner) { ?>
            <tr>
                <td>
                    <input type="hidden" name="wb_id[]" value="<?php echo (int) $banner['wb_id']; ?>">
                    <select name="wb_position[]">
                        <?php foreach ($positions as $key => $label) { ?>
                        <option value="<?php echo $key; ?>" <?php echo $banner['wb_position'] === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </td>
                <td><input type="number" name="wb_sort[]" value="<?php echo (int) $banner['wb_sort']; ?>" class="frm_input" style="width:70px"></td>
                <td>
                    <input type="text" name="wb_title[]" value="<?php echo get_text($banner['wb_title']); ?>" class="frm_input" style="width:190px" maxlength="255" placeholder="관리용 제목"><br>
                    <input type="text" name="wb_alt[]" value="<?php echo get_text($banner['wb_alt']); ?>" class="frm_input" style="width:190px;margin-top:4px" maxlength="255" placeholder="이미지 설명">
                </td>
                <td><input type="text" name="wb_url[]" value="<?php echo get_text($banner['wb_url']); ?>" class="frm_input" style="width:220px" maxlength="255" placeholder="https:// 또는 /willow/..."></td>
                <td>
                    <?php if ($banner['wb_image']) { ?>
                    <div class="willow_banner_preview" style="margin-bottom:6px">
                        <img src="<?php echo willow_banner_image_url($banner['wb_image']); ?>" alt="">
                    </div>
                    <label><input type="checkbox" name="wb_image_delete[<?php echo (int) $banner['wb_id']; ?>]" value="1"> 이미지 삭제</label><br>
                    <?php } ?>
                    <input type="file" name="wb_image_<?php echo (int) $banner['wb_id']; ?>" accept="image/jpeg,image/png,image/gif,image/webp">
                </td>
                <td class="td_mng"><input type="checkbox" name="wb_new_win[<?php echo (int) $banner['wb_id']; ?>]" value="1" <?php echo $banner['wb_new_win'] ? 'checked' : ''; ?>></td>
                <td class="td_mng"><input type="checkbox" name="wb_active[<?php echo (int) $banner['wb_id']; ?>]" value="1" <?php echo $banner['wb_active'] ? 'checked' : ''; ?>></td>
                <td class="td_mng"><input type="checkbox" name="wb_delete[<?php echo (int) $banner['wb_id']; ?>]" value="1"></td>
            </tr>
            <?php } ?>
            <?php } else { ?>
            <tr>
                <td colspan="8" class="empty_table">등록된 배너가 없습니다.</td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <input type="submit" value="목록 저장" class="btn_submit btn">
    </div>
</form>

<?php
require_once './admin.tail.php';
