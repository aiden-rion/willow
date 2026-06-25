<?php
$sub_menu = '700200';
require_once './_common.php';
include_once G5_PATH.'/willow/content.lib.php';

auth_check_menu($auth, $sub_menu, 'r');
willow_category_install();

$g5['title'] = '카테고리';
$categories = willow_get_categories(false);

require_once './admin.head.php';
?>

<form name="fwillowcategory" method="post" action="./willow_category_update.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

    <div class="local_desc01 local_desc">
        <p>메인 화면 카테고리와 검색 페이지 추천 태그가 동일한 카테고리 목록을 사용합니다. 검색어가 비어 있으면 전체보기 링크로 동작합니다.</p>
    </div>

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?></caption>
            <thead>
            <tr>
                <th scope="col">정렬</th>
                <th scope="col">라벨</th>
                <th scope="col">검색어</th>
                <th scope="col">노출</th>
                <th scope="col">삭제</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $category) { ?>
            <tr>
                <td class="td_num">
                    <input type="hidden" name="wc_id[]" value="<?php echo (int) $category['id']; ?>">
                    <input type="number" name="wc_sort[]" value="<?php echo (int) $category['sort']; ?>" class="frm_input" style="width:70px">
                </td>
                <td><input type="text" name="wc_label[]" value="<?php echo get_text($category['label']); ?>" class="frm_input" style="width:180px" maxlength="100"></td>
                <td><input type="text" name="wc_keyword[]" value="<?php echo get_text($category['keyword']); ?>" class="frm_input" style="width:180px" maxlength="100"></td>
                <td class="td_mng"><input type="checkbox" name="wc_active[<?php echo (int) $category['id']; ?>]" value="1" <?php echo $category['active'] ? 'checked' : ''; ?>></td>
                <td class="td_mng"><input type="checkbox" name="wc_delete[<?php echo (int) $category['id']; ?>]" value="1"></td>
            </tr>
            <?php } ?>
            <?php for ($i = 0; $i < 3; $i++) { ?>
            <tr>
                <td class="td_num">
                    <input type="hidden" name="new_wc_id[]" value="">
                    <input type="number" name="new_wc_sort[]" value="<?php echo 100 + ($i * 10); ?>" class="frm_input" style="width:70px">
                </td>
                <td><input type="text" name="new_wc_label[]" value="" class="frm_input" style="width:180px" maxlength="100" placeholder="#새카테고리"></td>
                <td><input type="text" name="new_wc_keyword[]" value="" class="frm_input" style="width:180px" maxlength="100" placeholder="검색어"></td>
                <td class="td_mng"><input type="checkbox" name="new_wc_active[<?php echo $i; ?>]" value="1" checked></td>
                <td class="td_mng">추가</td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <input type="submit" value="저장" class="btn_submit btn">
    </div>
</form>

<?php
require_once './admin.tail.php';
