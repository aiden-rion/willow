<?php
// Isolated provider test: no database, credentials or external requests.
class Hybrid_Provider_Model_OAuth2 {
    public $providerId = 'Kakao';
    private $tokens = array();
    public function token($name, $value = null) {
        if ($value !== null) $this->tokens[$name] = $value;
        return $this->tokens[$name] ?? null;
    }
}
require __DIR__.'/../plugin/social/Hybrid/Providers/Kakao.php';
foreach (array('missing', 'mismatch', 'expired', 'valid') as $case) {
    $provider = new Hybrid_Providers_Kakao();
    $provider->token('oauth_state', $case === 'missing' ? '' : 'expected');
    $provider->token('oauth_state_time', time() - ($case === 'expired' ? 601 : 0));
    $_GET = array('state' => $case === 'mismatch' ? 'wrong' : 'expected');
    $_REQUEST = array('error' => 'access_denied');
    try {
        $provider->loginFinish();
        throw new RuntimeException('Expected rejection');
    } catch (Exception $error) {
        $state_error = strpos($error->getMessage(), 'Invalid or expired') !== false;
        if ($state_error !== ($case !== 'valid')) throw $error;
        if ($provider->token('oauth_state') !== '') throw new RuntimeException('State not consumed');
    }
}
echo "Kakao missing, mismatched, expired and valid state tests passed.\n";
