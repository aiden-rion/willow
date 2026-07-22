<?php
include_once('./_common.php');
$g5['title'] = '오프라인';
include_once(G5_PATH.'/head.sub.php');
?>
<main class="willow_offline" role="main">
    <img src="<?php echo G5_IMG_URL; ?>/m_logo.png" alt="WILLOW">
    <h1>연결을 확인해주세요</h1>
    <p>네트워크가 다시 연결되면 WILLOW를 이어서 이용할 수 있습니다.</p>
    <button type="button" onclick="window.location.reload();">다시 시도</button>
</main>
<style>
.willow_offline {
    display: grid;
    min-height: 100vh;
    min-height: 100dvh;
    align-content: center;
    justify-items: center;
    gap: 16px;
    padding: 32px 24px calc(32px + env(safe-area-inset-bottom));
    background: #ffffff;
    color: #111111;
    text-align: center;
    font-family: "SUIT Variable", SUIT, -apple-system, BlinkMacSystemFont, sans-serif;
}
.willow_offline img {
    width: 112px;
    height: auto;
}
.willow_offline h1 {
    margin: 14px 0 0;
    font-size: 22px;
    line-height: 1.3;
}
.willow_offline p {
    max-width: 280px;
    margin: 0;
    color: #777777;
    font-size: 15px;
    line-height: 1.55;
}
.willow_offline button {
    min-width: 132px;
    height: 46px;
    margin-top: 8px;
    border: 0;
    border-radius: 6px;
    background: #f0333d;
    color: #ffffff;
    font: inherit;
    font-size: 15px;
    font-weight: 700;
}
</style>
<?php
include_once(G5_PATH.'/tail.sub.php');
