<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css?v='.filemtime($board_skin_path.'/style.css').'">', 0);

$willow_topics = array(
    array('value' => 'today', 'label' => '오늘의 주제', 'title' => '인권', 'description' => '인권이 우리 사회에 미치는 영향에 대해<br>깊이 생각해보게 되었습니다.'),
    array('value' => 'free', 'label' => '자유주제', 'title' => '자유주제', 'description' => ''),
);
$willow_selected_topic = $w == 'u' && !empty($write['wr_1']) ? $write['wr_1'] : 'today';
$willow_selected_access = $w == 'u' && !empty($write['wr_3']) ? $write['wr_3'] : 'public';
$willow_selected_tags = $w == 'u' && !empty($write['wr_2']) ? array_filter(array_map('trim', explode(',', $write['wr_2']))) : array();
$willow_tags = array('정착꿀팁', '정착상식', '정신건강', '범죄사건', '종교문화', '이야기', '사람', '민주주의', '인권');
?>

<section id="bo_w">
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="wr_2" value="<?php echo isset($write['wr_2']) ? get_text($write['wr_2']) : ''; ?>" id="willow_selected_tags">
    <?php
    $option = '';
    $option_hidden = '';
    if ($is_notice || $is_html || $is_secret || $is_mail) { 
        $option = '';
        if ($is_notice) {
            $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="notice" name="notice"  class="selec_chk" value="1" '.$notice_checked.'>'.PHP_EOL.'<label for="notice"><span></span>공지</label></li>';
        }
        if ($is_html) {
            if ($is_dhtml_editor) {
                $option_hidden .= '<input type="hidden" value="html1" name="html">';
            } else {
                $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" class="selec_chk" value="'.$html_value.'" '.$html_checked.'>'.PHP_EOL.'<label for="html"><span></span>html</label></li>';
            }
        }
        if ($is_secret) {
            if ($is_admin || $is_secret==1) {
                $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="secret" name="secret"  class="selec_chk" value="secret" '.$secret_checked.'>'.PHP_EOL.'<label for="secret"><span></span>비밀글</label></li>';
            } else {
                $option_hidden .= '<input type="hidden" name="secret" value="secret">';
            }
        }
        if ($is_mail) {
            $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="mail" name="mail"  class="selec_chk" value="mail" '.$recv_email_checked.'>'.PHP_EOL.'<label for="mail"><span></span>답변메일받기</label></li>';
        }
    }
    echo $option_hidden;
    ?>
	
    <div class="form_01 write_div willow_write_screen">
        <h2 class="sound_only"><?php echo $g5['title'] ?></h2>

        <div class="willow_topic_select">
            <label for="willow_topic" class="sound_only">주제 선택</label>
            <select name="wr_1" id="willow_topic" aria-label="주제 선택">
                <?php foreach ($willow_topics as $topic) { ?>
                <option value="<?php echo $topic['value']; ?>" data-title="<?php echo $topic['title']; ?>" data-description="<?php echo $topic['description']; ?>"<?php echo get_selected($willow_selected_topic, $topic['value']); ?>><?php echo $topic['label']; ?></option>
                <?php } ?>
            </select>
            <i class="fa fa-angle-down" aria-hidden="true"></i>
        </div>

        <div class="willow_topic_intro" data-topic-card>
            <strong data-topic-title>인권</strong>
            <p data-topic-description>인권이 우리 사회에 미치는 영향에 대해<br>깊이 생각해보게 되었습니다.</p>
        </div>

        <?php if ($is_category) { ?>
        <div class="bo_w_select write_div">
            <label for="ca_name" class="sound_only">분류<strong>필수</strong></label>
            <select id="ca_name" name="ca_name" required>
                <option value="">선택하세요</option>
                <?php echo $category_option ?>
            </select>
        </div>
        <?php } ?> 
        
        <?php if ($is_name) { ?>
        <div class="write_div">
            <label for="wr_name" class="sound_only">이름<strong>필수</strong></label>
            <input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input full_input required" maxlength="20" placeholder="이름">
        </div>
        <?php } ?>

        <?php if ($is_password) { ?>
        <div class="write_div">
            <label for="wr_password" class="sound_only">비밀번호<strong>필수</strong></label>
            <input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input full_input <?php echo $password_required ?>" maxlength="20" placeholder="비밀번호">
        </div>
        <?php } ?>

        <?php if ($is_email) { ?>
        <div class="write_div">
            <label for="wr_email" class="sound_only">이메일</label>
            <input type="email" name="wr_email" value="<?php echo $email ?>" id="wr_email" class="frm_input full_input" maxlength="100" placeholder="이메일">
        </div>
        <?php } ?>

        <?php if ($is_homepage) { ?>
        <div class="write_div">
            <label for="wr_homepage" class="sound_only">홈페이지</label>
            <input type="text" name="wr_homepage" value="<?php echo $homepage ?>" id="wr_homepage" class="frm_input full_input" placeholder="홈페이지">
        </div>
        <?php } ?>

        <?php if ($option) { ?>
        <div class="write_div">
            <span class="sound_only">옵션</span>
            <ul class="bo_v_option">
            <?php echo $option ?>
            </ul>
        </div>
        <?php } ?>

        <div class="bo_w_tit write_div willow_subject_field">
            <label for="wr_subject" class="sound_only">제목<strong>필수</strong></label>
            <input type="text" name="wr_subject" value="<?php echo get_text($subject); ?>" id="wr_subject" maxlength="255" placeholder="제목을 입력해주세요" required>
        </div>

        <div class="write_div willow_content_area">
            <label for="wr_content" class="sound_only">내용<strong>필수</strong></label>
            <?php if($write_min || $write_max) { ?>
            <!-- 최소/최대 글자 수 사용 시 -->
            <p id="char_count_desc">이 게시판은 최소 <strong><?php echo $write_min; ?></strong>글자 이상, 최대 <strong><?php echo $write_max; ?></strong>글자 이하까지 글을 쓰실 수 있습니다.</p>
            <?php } ?>
            <?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
            <div id="char_count_wrap"><span id="char_count">0</span> / 500자</div>
        </div>

        <?php for ($i=1; $is_link && $i<=G5_LINK_COUNT; $i++) { ?>
        <div class="bo_w_link write_div">
            <label for="wr_link<?php echo $i ?>"><i class="fa fa-link" aria-hidden="true"></i> <span class="sound_only">링크 #<?php echo $i ?></span></label>
            <input type="text" name="wr_link<?php echo $i ?>" value="<?php if($w=="u"){echo $write['wr_link'.$i];} ?>" id="wr_link<?php echo $i ?>" class="frm_input wr_link" placeholder="링크를 입력하세요">
        </div>
        <?php } ?>

        <?php if ($is_file) { ?>
        <div class="willow_attach_wrap write_div">
        <?php for ($i=0; $i<$file_count; $i++) { ?>
        <div class="bo_w_flie willow_attach_item">
            <label for="bf_file_<?php echo $i+1 ?>" class="willow_attach_tile">
                <i class="fa fa-picture-o" aria-hidden="true"></i>
                <span class="sound_only">파일 #<?php echo $i+1 ?> 첨부</span>
                <img src="" alt="" class="willow_attach_preview">
                <button type="button" class="willow_attach_remove" aria-label="첨부 이미지 삭제"><i class="fa fa-times" aria-hidden="true"></i></button>
            </label>
            <input type="file" name="bf_file[]" id="bf_file_<?php echo $i+1 ?>" title="파일첨부 <?php echo $i+1 ?> : 용량 <?php echo $upload_max_filesize ?> 이하만 업로드 가능" class="frm_file willow_attach_input" accept="image/*">
            <?php if ($is_file_content) { ?>
            <input type="text" name="bf_content[]" value="<?php echo ($w == 'u') ? $file[$i]['bf_content'] : ''; ?>" title="파일 설명을 입력해주세요." class="full_input frm_input willow_hidden_field" size="50" placeholder="파일 설명을 입력해주세요.">
            <?php } ?>

            <?php if($w == 'u' && $file[$i]['file']) { ?>
            <span class="file_del">
                <input type="checkbox" id="bf_file_del<?php echo $i ?>" name="bf_file_del[<?php echo $i;  ?>]" value="1"> <label for="bf_file_del<?php echo $i ?>"><?php echo $file[$i]['source'].'('.$file[$i]['size'].')';  ?> 파일 삭제</label>
            </span>
            <?php } ?>
        </div>
        <?php } ?>
        </div>
        <?php } ?>

        <div class="willow_write_options">
            <div class="willow_option_group">
                <strong>감정태그선택</strong>
                <div class="willow_tag_chips" role="group" aria-label="감정태그선택">
                    <?php foreach ($willow_tags as $tag) {
                        $is_tag_selected = in_array($tag, $willow_selected_tags);
                    ?>
                    <button type="button" class="willow_tag_chip<?php echo $is_tag_selected ? ' is_selected' : ''; ?>" data-tag="<?php echo $tag; ?>">#<?php echo $tag; ?></button>
                    <?php } ?>
                </div>
            </div>

            <div class="willow_option_group">
                <label for="willow_access">구독자 전용 선택</label>
                <div class="willow_select_shell">
                    <select name="wr_3" id="willow_access">
                        <option value="public"<?php echo get_selected($willow_selected_access, 'public'); ?>>전체공개</option>
                        <option value="subscriber"<?php echo get_selected($willow_selected_access, 'subscriber'); ?>>구독자 전용</option>
                    </select>
                    <i class="fa fa-angle-down" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <?php if ($is_use_captcha) { //자동등록방지 ?>
        <div class="write_div">
            <span class="sound_only">자동등록방지</span>
            <?php echo $captcha_html ?>
        </div>
        <?php } ?>
    </div>

    <div class="btn_confirm willow_write_actions">
        <a href="<?php echo get_pretty_url($bo_table); ?>" class="btn_cancel">임시저장</a>
        <button type="submit" id="btn_submit" class="btn_submit" accesskey="s">작성완료</button>
    </div>
    </form>
</section>

<script>
var char_min = parseInt(<?php echo (int) $write_min; ?>);
var char_max = parseInt(<?php echo $write_max ? (int) $write_max : 500; ?>);
<?php if($write_min || $write_max) { ?>
// 글자수 제한
check_byte("wr_content", "char_count");

$(function() {
    $("#wr_content").on("keyup", function() {
        willow_update_char_count();
    });
});

<?php } ?>
document.body.classList.add("willow_write_page");

$(function() {
    var $pageTitle = $(".willow_page_title h2");
    if ($pageTitle.length) {
        $pageTitle.text("글쓰기");
    }

    var $topic = $("#willow_topic");
    var $subject = $("#wr_subject");
    var $topicCard = $("[data-topic-card]");
    var $topicTitle = $("[data-topic-title]");
    var $topicDescription = $("[data-topic-description]");
    var $content = $("#wr_content");
    var $tagValue = $("#willow_selected_tags");

    function syncTopic() {
        var selected = $topic.find("option:selected");
        var title = selected.data("title") || selected.text();
        var description = selected.data("description") || "";
        $topicTitle.text(title);
        $topicDescription.html(description);
        $topicCard.toggle($topic.val() !== "free");
        $content.attr("placeholder", $topic.val() === "free" ? "자유롭게 글을 작성해주세요" : "오늘의 주제에 대한 생각을\n자유롭게 작성해주세요");
    }

    function syncTags() {
        var tags = [];
        $(".willow_tag_chip.is_selected").each(function() {
            tags.push($(this).data("tag"));
        });
        $tagValue.val(tags.join(","));
    }

    window.willow_update_char_count = function() {
        var text = $content.val() || "";
        $("#char_count").text(text.length);
    };

    $topic.on("change", syncTopic);
    $content.on("input keyup", window.willow_update_char_count);
    $(".willow_tag_chip").on("click", function() {
        $(this).toggleClass("is_selected");
        syncTags();
    });
    $(".willow_attach_input").on("change", function() {
        var input = this;
        var $item = $(input).closest(".willow_attach_item");
        var file = input.files && input.files[0];
        if (!file) {
            $item.removeClass("has_preview");
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            $item.find(".willow_attach_preview").attr("src", e.target.result);
            $item.addClass("has_preview");
        };
        reader.readAsDataURL(file);
    });
    $(".willow_attach_remove").on("click", function(e) {
        e.preventDefault();
        var $item = $(this).closest(".willow_attach_item");
        var input = $item.find(".willow_attach_input")[0];
        if (input) input.value = "";
        $item.removeClass("has_preview");
        $item.find(".willow_attach_preview").attr("src", "");
    });

    syncTopic();
    syncTags();
    window.willow_update_char_count();
});

function html_auto_br(obj)
{
    if (obj.checked) {
        result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
        if (result)
            obj.value = "html2";
        else
            obj.value = "html1";
    }
    else
        obj.value = "";
}

function fwrite_submit(f)
{
    if (typeof willow_update_char_count === "function") {
        willow_update_char_count();
    }
    if (!f.wr_subject.value) {
        f.wr_subject.value = $("#willow_topic option:selected").data("title") || "자유주제";
    }
    <?php echo $editor_js; // 에디터 사용시 자바스크립트에서 내용을 폼필드로 넣어주며 내용이 입력되었는지 검사함   ?>

    var subject = "";
    var content = "";
    $.ajax({
        url: g5_bbs_url+"/ajax.filter.php",
        type: "POST",
        data: {
            "subject": f.wr_subject.value,
            "content": f.wr_content.value
        },
        dataType: "json",
        async: false,
        cache: false,
        success: function(data, textStatus) {
            subject = data.subject;
            content = data.content;
        }
    });

    if (subject) {
        alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
        f.wr_subject.focus();
        return false;
    }

    if (content) {
        alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
        if (typeof(ed_wr_content) != "undefined")
            ed_wr_content.returnFalse();
        else
            f.wr_content.focus();
        return false;
    }

    if (document.getElementById("char_count")) {
        if (char_min > 0 || char_max > 0) {
            var cnt = parseInt(check_byte("wr_content", "char_count"));
            if (char_min > 0 && char_min > cnt) {
                alert("내용은 "+char_min+"글자 이상 쓰셔야 합니다.");
                return false;
            }
            else if (char_max > 0 && char_max < cnt) {
                alert("내용은 "+char_max+"글자 이하로 쓰셔야 합니다.");
                return false;
            }
        }
    }

    <?php echo $captcha_js; // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함  ?>

    document.getElementById("btn_submit").disabled = "disabled";

    return true;
}

var uploadFile = $('.filebox .uploadBtn');
uploadFile.on('change', function(){
	if(window.FileReader){
		var filename = $(this)[0].files[0].name;
	} else {
		var filename = $(this).val().split('/').pop().split('\\').pop();
	}
	$(this).siblings('.fileName').val(filename);
});
</script>
