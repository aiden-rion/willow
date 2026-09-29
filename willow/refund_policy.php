<?php
include_once('./_common.php');

$g5['title'] = '환불정책';
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/willow_content.css?ver='.G5_CSS_VER.'">', 10);
?>

<main class="willow_content_app willow_policy_page">
    <header class="willow_author_page_header willow_policy_header">
        <a href="<?php echo G5_URL; ?>/willow/menu.php" aria-label="뒤로가기"><img src="<?php echo G5_IMG_URL; ?>/ico_back.png" alt=""></a>
        <h1>환불정책</h1>
    </header>

    <section class="willow_policy_hero">
        <h2>WILLOW 정기구독<br>결제 및 환불 안내</h2>
        <p>윌로우는 작가의 유료 글을 월 단위로 이용하는 정기구독 서비스를 제공합니다.</p>
    </section>

    <section class="willow_policy_section">
        <h3>결제 상품/서비스</h3>
        <p>작가별 월 정기구독 상품입니다. 구독자는 선택한 작가의 구독자 전용 글과 관련 콘텐츠를 이용할 수 있습니다.</p>
        <dl>
            <div>
                <dt>결제주기</dt>
                <dd>월 1회 자동결제</dd>
            </div>
            <div>
                <dt>상품금액</dt>
                <dd>작가별 구독료 기준, 기본 월 8,800원</dd>
            </div>
            <div>
                <dt>서비스 제공</dt>
                <dd>결제 완료 즉시 구독자 전용 콘텐츠 열람 가능</dd>
            </div>
        </dl>
    </section>

    <section class="willow_policy_section">
        <h3>환불 기준</h3>
        <ul>
            <li>결제 후 구독자 전용 콘텐츠를 열람하지 않은 경우 결제일로부터 7일 이내 환불 요청이 가능합니다.</li>
            <li>구독자 전용 콘텐츠를 열람했거나 서비스 이용 내역이 있는 경우 해당 월 이용분은 환불이 제한될 수 있습니다.</li>
            <li>중복 결제, 시스템 오류, 결제 승인 오류로 확인되는 경우 이용 여부와 관계없이 확인 후 환불 처리합니다.</li>
            <li>정기구독 해지는 다음 결제일부터 적용되며, 이미 결제된 이용 기간은 만료일까지 유지됩니다.</li>
        </ul>
    </section>

    <section class="willow_policy_section">
        <h3>환불 신청 방법</h3>
        <p>환불이 필요한 경우 고객센터 또는 1:1 문의를 통해 결제일, 구독 작가명, 환불 사유를 전달해 주세요.</p>
        <dl>
            <div>
                <dt>처리기간</dt>
                <dd>접수 후 영업일 기준 3~5일 이내 확인</dd>
            </div>
            <div>
                <dt>환불수단</dt>
                <dd>결제에 사용한 카드 승인 취소 또는 PG사 기준 환불</dd>
            </div>
        </dl>
    </section>

    <nav class="willow_policy_actions">
        <a href="<?php echo G5_URL; ?>/willow/subscribe.php">구독 상품 보기</a>
        <a href="<?php echo G5_URL; ?>">메인으로</a>
    </nav>
</main>

<?php
include_once(G5_PATH.'/tail.sub.php');
