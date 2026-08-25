<?php
if (!defined('_GNUBOARD_')) exit;

function willow_story_categories()
{
    return array(
        'north_life' => array(
            'label' => '북한에서의 삶',
            'full_label' => '북한에서 나의 경험',
            'description' => '일상, 가족, 학교, 노동, 탈북까지 삶의 장면',
            'tags' => array('북한의 하루', '탁아소', '나의 세대', '결혼 생활', '사랑·이별', '육아', '성생활', '군대', '탈북 이야기', '조직생활', '학교', '노동', '음식', '자유 주제'),
        ),
        'essay' => array(
            'label' => '감성 에세이',
            'full_label' => '감성 에세이',
            'description' => '시, 편지, 일기처럼 마음을 담은 글',
            'tags' => array('시', '북에 보내는 편지', '오늘의 일기', '미안했습니다', '사랑·이별', '그리운 사람'),
        ),
        'human_rights' => array(
            'label' => '인권·폭력 경험',
            'full_label' => '인권-폭력 경험',
            'description' => '침해, 폭력, 북송과 제3국 경험 기록',
            'tags' => array('감옥·교화소', '인권 유린', '제3국 경험', '북송 경험', '가해자 고발'),
        ),
        'north_today' => array(
            'label' => '오늘의 북한',
            'full_label' => '오늘의 북한',
            'description' => '지금 북한의 사회, 경제, 통제와 생활 변화',
            'tags' => array('오늘의 북한', '시장 물가', '사건사고', '사회 유행', '검열·통제', '주민 생활 변화', '영상·사진·자료', '정치', '경제', '군사', '장마당', '문화'),
        ),
        'south_settlement' => array(
            'label' => '한국 정착기',
            'full_label' => '대한민국 경험',
            'description' => '하나원부터 학교, 직장, 여행, 음식까지',
            'tags' => array('국정원', '하나원', '정착지원', '연애 이야기', '대학·직장', '남북차이', '육아', '나의 첫 알바', '여행', '음식', '기타'),
        ),
        'find_people' => array(
            'label' => '사람을 찾습니다',
            'full_label' => '사람을 찾습니다',
            'description' => '보고 싶은 사람, 소식이 궁금한 사람',
            'tags' => array('사람을 찾습니다'),
        ),
        'free' => array(
            'label' => '자유 주제',
            'full_label' => '자유 주제',
            'description' => '정해진 틀 없이 자유롭게 쓰는 이야기',
            'tags' => array('자유 주제'),
        ),
        'south_society' => array(
            'label' => '한국 사회',
            'full_label' => '한국의 사회·정치·경제·문화',
            'description' => '한국 사회를 보며 느낀 생각과 관찰',
            'tags' => array('정치', '경제', '문화', '사회', '민주주의'),
        ),
        'north_society' => array(
            'label' => '북한 사회·문화',
            'full_label' => '북한의 사회·정치·경제·문화·예술',
            'description' => '북한의 음식, 문화, 예술, 학교와 교육',
            'tags' => array('북한 음식·문화', '예술·노래·영화', '북한의 학교와 교육'),
        ),
        'north_world' => array(
            'label' => '북한과 세계',
            'full_label' => '내가 본 북한과 세계',
            'description' => '북한을 둘러싼 세계, 국제정책, 미래 한반도',
            'tags' => array('북한-중국 관계', '북핵', '국제사회 대북 정책', '북한 문화·예술', '미래 한반도의 모습', '북한 경제'),
        ),
    );
}

function willow_story_default_category()
{
    return 'north_life';
}

function willow_story_category_exists($category_key)
{
    $categories = willow_story_categories();

    return isset($categories[$category_key]);
}

function willow_story_normalize_category($category_key)
{
    $category_key = preg_replace('/[^a-z0-9_]/', '', (string) $category_key);

    return willow_story_category_exists($category_key) ? $category_key : willow_story_default_category();
}

function willow_story_category_label($category_key, $fallback = '자유 주제')
{
    $categories = willow_story_categories();
    $category_key = willow_story_normalize_category($category_key);

    return isset($categories[$category_key]['label']) ? $categories[$category_key]['label'] : $fallback;
}

function willow_story_tag_labels($category_key, $tags)
{
    $category_key = willow_story_normalize_category($category_key);
    $categories = willow_story_categories();
    $allowed = isset($categories[$category_key]['tags']) ? $categories[$category_key]['tags'] : array();
    $allowed_map = array();
    foreach ($allowed as $tag) {
        $allowed_map[$tag] = true;
    }

    $result = array();
    foreach ((array) $tags as $tag) {
        $tag = trim(strip_tags((string) $tag));
        if ($tag === '' || !isset($allowed_map[$tag]) || in_array($tag, $result, true)) {
            continue;
        }
        $result[] = $tag;
    }

    return $result;
}

function willow_story_normalize_tags($category_key, $tag_string, $limit = 4)
{
    $tags = array();
    foreach (explode(',', (string) $tag_string) as $tag) {
        $tags[] = $tag;
    }

    $tags = willow_story_tag_labels($category_key, $tags);
    $limit = max(1, (int) $limit);

    return implode(',', array_slice($tags, 0, $limit));
}

function willow_story_flat_search_categories()
{
    $items = array(array(
        'id' => 0,
        'label' => '전체보기',
        'keyword' => '',
        'sort' => 0,
        'active' => 1,
        'href' => G5_BBS_URL.'/search.php',
    ));
    $sort = 10;

    foreach (willow_story_categories() as $category_key => $category) {
        $items[] = array(
            'id' => $sort,
            'label' => '#'.$category['label'],
            'keyword' => $category['label'],
            'sort' => $sort,
            'active' => 1,
            'href' => G5_BBS_URL.'/search.php?sfl=wr_subject%7C%7Cwr_content&sop=or&stx='.urlencode($category['label']),
        );
        $sort += 10;

        foreach ($category['tags'] as $tag) {
            $items[] = array(
                'id' => $sort,
                'label' => '#'.$tag,
                'keyword' => $tag,
                'sort' => $sort,
                'active' => 1,
                'href' => G5_BBS_URL.'/search.php?sfl=wr_subject%7C%7Cwr_content&sop=or&stx='.urlencode($tag),
            );
            $sort += 10;
        }
    }

    return $items;
}
