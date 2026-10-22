<?php
if (!defined('_GNUBOARD_')) exit;

function willow_seo_metadata($script, $query, $title, $context = array())
{
    $base = 'https://willlow.org';
    $description = '작은 기록이 서로의 힘이 되는 곳, 윌로우. 일상의 이야기와 탈북이주민 작가의 글을 만나보세요.';
    $canonical = $base.$script;
    $index = false;
    $type = 'website';
    if ($script === '/index.php' || $script === '/') {
        $title = '윌로우 WILLOW | 작은 기록이 서로의 힘이 되는 곳';
        $canonical = $base.'/';
        $index = true;
    } elseif ($script === '/willow/post.php' && !empty($context['post_id'])) {
        $canonical .= '?'.($context['post_type'] === 'topic' ? 'wp_id=' : 'wr_id=').(int) $context['post_id'];
        $index = empty($context['paid']);
        $type = 'article';
        $description = $index ? ($context['excerpt'] ?? '') : '구독자 전용 글입니다. 작가 구독 후 읽을 수 있습니다.';
    } elseif ($script === '/willow/author.php' && !empty($context['author_id'])) {
        $canonical .= '?author='.rawurlencode($context['author_id']);
        $index = !empty($context['certified']);
        $title = ($context['author_name'] ?? '').($index ? ' 작가' : ' 프로필').' | 윌로우';
        $description = $context['profile'] ?? '';
    } elseif ($script === '/bbs/faq.php' || $script === '/willow/refund_policy.php') {
        $index = true;
        $description = $script === '/bbs/faq.php' ? '윌로우 회원가입, 글쓰기, 작가 구독과 서비스 이용에 관한 자주 묻는 질문입니다.' : '윌로우 월 정기구독의 결제주기, 해지 및 환불정책을 안내합니다.';
    } elseif ($script === '/bbs/board.php' && ($query['bo_table'] ?? '') === 'notice') {
        $index = true;
        $canonical .= '?bo_table=notice';
        if (!empty($query['wr_id'])) $canonical .= '&wr_id='.(int) $query['wr_id'];
        elseif (!empty($query['page']) && (int) $query['page'] > 1) $canonical .= '&page='.(int) $query['page'];
        $description = '윌로우 서비스의 새로운 소식과 공지사항을 확인하세요.';
    }
    $description = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8')));
    $description = mb_substr($description, 0, 150, 'UTF-8');
    if ($description === '') $description = '윌로우에서 글과 작가의 이야기를 만나보세요.';
    return array('title' => strip_tags($title), 'description' => $description, 'canonical' => $canonical, 'index' => $index, 'type' => $type);
}
