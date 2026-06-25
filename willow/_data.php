<?php
if (!defined('_GNUBOARD_')) exit;

$willow_author_avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=160&q=80';
$willow_video_thumb = 'https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=900&q=80';

$willow_feed_posts = array(
    array(
        'author' => '장영진',
        'date' => '2026.01.01',
        'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=160&q=80',
        'body' => '북한 탈북자들의 인권을 위한 최신 이니셔티브에 대해 이야기하게 되어 매우 기쁩니다! 그들의 목소리가 들리고 권리가 보호받는 것이 중요합니다. 이 중요한 문제에 대한 여러분의 동참이나 이야기를 나누어 주세요. 함께 지지하는 커뮤니티를 만들어 봅시다!',
        'likes' => '30',
        'comments' => '30',
        'actions' => true,
        'href' => G5_URL.'/willow/post.php?type=general',
    ),
    array(
        'author' => '김나영',
        'date' => '2026.01.01',
        'avatar' => $willow_author_avatar,
        'body' => '북한 탈북자들이 직면한 도전 과제를 탐구하는 것은 매우 깨달음을 주었습니다! 그들의 권리와 복지를 논의에서 우선시해야 합니다. 그들의 권리를 옹호하는 데 열정적인 다른 분들과 연결되기를 기대합니다!',
        'image' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80',
        'liked' => true,
        'likes' => '1,220',
        'comments' => '30',
        'verified' => true,
        'href' => G5_URL.'/willow/post.php?type=paid',
    ),
);

$willow_recommended_posts = array(
    array(
        'title' => '남한 사회의 이방인인 탈북자',
        'excerpt' => '이 사회가 나를 받아들이기 이전에, 내가 나를 받아들이고 사랑할 수 있을까? 우리들의 밀려왔다.',
        'author' => '김나영 작가',
        'date' => '2026.01.01',
        'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80',
    ),
    array(
        'title' => '북한이탈주민들의 정착에서 어려움을 겪는 가장 큰 요소로 완곡어법을 들 수 있다',
        'excerpt' => '직장에서 한국말이 쓰이는 경우도 많아 탈북민을 고용한 회사에서는 탈북민들이 솔직함을 중시한다.',
        'author' => '김나영 작가',
        'date' => '2026.01.01',
        'image' => 'https://images.unsplash.com/photo-1499952127939-9bbf5af6c51c?auto=format&fit=crop&w=300&q=80',
    ),
    array(
        'title' => '탈북민에 대한 인식 중 여성의 생활력이 강하다든가 직설화법을 추구하는 성향',
        'excerpt' => '역설 말투는 북한에서도 함경도 지역에서 두드러진다고 알려져 있다.',
        'author' => '김나영 작가',
        'date' => '2026.01.01',
        'image' => 'https://images.unsplash.com/photo-1520813792240-56fc4a3765a7?auto=format&fit=crop&w=300&q=80',
    ),
);

$willow_article_paragraphs = array(
    '지난 2025년 12월 29일, 사단법인 통일의 징검다리 우리온이 하나원 오늘의 사회복지공동모금회의 지원을 받아 추진한 북한이탈주민 가정 내 심리·정서적 안정 도모를 위한 맞춤형 멘토링 사업을 성공적으로 마무리했다고 밝혔다. 해당 사업은 크게 영상 기반 심리치료 콘텐츠 제작과 오프라인 1:1 심리상담으로 구분되며, 이번 기사는 이 가운데 영상 상담 콘텐츠를 중심으로 한 비대면 심리지원 운영 성과를 다룬다.',
    '이 사업은 대면 상담이 어려운 북한이탈주민 가정을 위해 기획되었으며, 우리온은 상담 과정에서 축적된 사례를 바탕으로 재연 드라마와 솔루션형 상담 영상을 제작하여 배포하였다.',
);
