# WILLOW 작업 인수인계 메모

이 문서는 Codex 채팅 세션이 바뀌어도 운영/개발 작업을 이어가기 위한 기본 정보만 정리한다.
비밀번호, API SecretKey, 결제 Secret, PEM 파일 내용 같은 민감정보는 저장소에 기록하지 않는다.

## 프로젝트 위치

- 로컬 프로젝트 루트: `/Users/aiden/Project/Willow`
- 로컬 소스 루트: `/Users/aiden/Project/Willow/gnuboard`
- 운영 웹루트: `/var/www/willow`
- 운영 도메인: `https://willlow.org`
- 운영 IP: `3.37.249.219`
- 도메인은 `willlow.org`로 `l`이 3개다.

## Git

- 로컬 작업 브랜치: `main`
- 운영 배포 브랜치: `origin/main`
- 로컬 push remote 예시: `willow git@github.com:aiden-rion/willow.git`
- 운영 서버는 `/var/www/willow`에서 `git pull --ff-only origin main` 방식으로 반영한다.

기본 배포 흐름:

```bash
cd /Users/aiden/Project/Willow/gnuboard
git status --short
git add <changed-files>
git commit -m "<message>"
git push willow main
ssh -i /Users/aiden/Project/Willow/willow_kr.pem -o StrictHostKeyChecking=no ec2-user@3.37.249.219 'cd /var/www/willow && git pull --ff-only origin main'
```

배포 후 PHP 문법 확인 예시:

```bash
ssh -i /Users/aiden/Project/Willow/willow_kr.pem -o StrictHostKeyChecking=no ec2-user@3.37.249.219 'cd /var/www/willow && php -l path/to/file.php && git rev-parse --short HEAD'
```

## SSH

- 키 파일 경로: `/Users/aiden/Project/Willow/willow_kr.pem`
- 접속 사용자: `ec2-user`
- 접속 명령:

```bash
ssh -i /Users/aiden/Project/Willow/willow_kr.pem -o StrictHostKeyChecking=no ec2-user@3.37.249.219
```

키 파일 자체는 저장소에 넣지 않는다.

## 로컬 서버

로컬 PHP 서버가 필요하면 다음처럼 실행한다.

```bash
cd /Users/aiden/Project/Willow/gnuboard
php -S 127.0.0.1:8083 -t /Users/aiden/Project/Willow/gnuboard
```

주의: 로컬 DB 설정이 맞지 않으면 `MySQL Host, User, Password, DB 정보에 오류가 있습니다.`가 표시될 수 있다.

## 외부 연동 상태

- Popbill SMS: 운영 발신번호 `02-2649-0206` 기준으로 테스트 발송 성공 이력이 있다.
- Popbill LinkID: `WILLOWTEAM`
- Popbill 회원 ID: `willowteam28`
- Popbill SecretKey와 비밀번호는 저장소에 기록하지 않는다.
- Toss 결제: 카드 등록/정기결제 흐름을 Willow 결제 라이브러리와 사용자 화면에 연결해둔 상태다.
- Toss/Popbill 운영 전환값은 서버 환경값 또는 설정 파일에서 확인하고, 비밀키는 문서화하지 않는다.

## 테스트/운영 확인용 계정 메모

저장소에는 실제 비밀번호를 기록하지 않는다.

- 테스트 휴대폰 계정: `010-0000-0000`
- 테스트 계정은 인증번호 `000000`으로 로그인 가능하도록 처리한 이력이 있다.
- 작가 테스트 휴대폰 예시: `01010000001`
- 탈북이주민 인증 작가만 작가로 인정하고, 작가 배지는 `img/img_cert_mark.png`를 사용한다.

## 주요 최근 작업 흐름

- 최근 기능 운영 반영 커밋: `194e8e3`
- 이 인수인계 문서 반영 커밋: `28188c9`
- 최근 CSS 캐시 버전: `G5_CSS_VER = 2610113`
- PC 화면에서는 모바일 UI를 `390px` 프레임으로 보여주고 배경에는 WILLOW 소개/검색 영역을 배치한다.
- PC 배경 오른쪽 소개 영역은 제거했고, 왼쪽에 로고/카피/글 검색창/주요 키워드 칩을 배치했다.
- 모바일 프레임 내부 콘텐츠가 PC에서 오른쪽으로 삐져나가지 않도록 `.willow_app`, 카드, 배너, 추천영역의 폭을 프레임 내부 기준으로 제한했다.
- CSS 캐시는 `extend/version.extend.php`의 `G5_CSS_VER`를 올려 갱신한다.
- PC 프레임 CSS는 기본적으로 `@media (min-width: 1180px)` 안에서만 적용한다.
- PC 프레임에서 `#container.willow_container`는 `#wrapper.willow_shell` 내부 요소라서 `calc(50% + 40px)`로 다시 밀면 모바일 화면이 깨진다. 데스크톱에서는 `#hd.willow_shell_header`와 `#wrapper.willow_shell`만 프레임 위치로 이동하고, `#container.willow_container`는 `margin-left: 0; margin-right: 0;` 기준을 유지한다.
- `willow/menu.php`는 일반 모바일 공통 wrapper가 아니라 `head.sub.php` 계열을 사용하므로 `body.willow_menu_body .willow_menu_page`에 별도 `390px` 프레임 규칙이 필요하다.
- 글 상세/구독 등 독립 앱형 화면은 `theme/willow_mobile/css/willow_content.css`에서 `.willow_content_app`, `.willow_subscribe_app`, `.willow_bottom_nav`를 같이 프레임 처리한다.
- 회원/로그인 화면에서 실제 로딩되는 스킨은 `/mobile/skin/member/basic`이다. 테마 복사본인 `theme/willow_mobile/mobile/skin/member/basic`만 수정하면 운영 화면에 반영되지 않을 수 있다.
- 회원가입/회원확인 CSS는 `mobile/skin/member/basic/style.css`, 로그인 CSS는 `mobile/skin/member/basic/willow_auth.css`가 우선 확인 대상이다. 최근 작업에서는 테마 복사본에도 동일 변경을 맞춰두었다.
- 회원가입 스킨의 CSS는 `mobile/skin/member/basic/register_form.skin.php`와 테마 복사본에서 `filemtime()` 기반으로 버전 처리한다.
- 스플래시 화면은 현재 `splash_001.png`부터 `splash_008.png`까지 8개 이미지를 사용한다.
- 스플래시는 모바일 touch 이벤트와 PC pointer/mouse 드래그를 모두 지원한다. PC에서 옆으로 넘기는 동작이 안 보이면 `willow/splash.php`의 `pointerdown`, `pointermove`, `pointerup`, `beginDrag` 코드와 `theme/willow_mobile/css/willow_splash.css`의 `cursor: grab` 규칙을 확인한다.
- 로그인 상태에서 `/willow/splash.php`는 return URL로 넘어갈 수 있으므로, 스플래시 자체 확인은 로그아웃 상태 또는 `curl` 소스 확인으로 진행한다.

## 자주 확인하는 파일

- 공통 모바일 헤더: `theme/willow_mobile/mobile/head.php`
- 공통 모바일 푸터: `theme/willow_mobile/mobile/tail.php`
- 메인 홈: `theme/willow_mobile/mobile/index.php`
- 스플래시: `willow/splash.php`
- 모바일 공통 CSS: `theme/willow_mobile/css/willow_mobile.css`
- 스플래시 CSS: `theme/willow_mobile/css/willow_splash.css`
- 콘텐츠/글 상세 CSS: `theme/willow_mobile/css/willow_content.css`
- 실제 회원 스킨 CSS: `mobile/skin/member/basic/style.css`
- 실제 로그인 CSS: `mobile/skin/member/basic/willow_auth.css`
- 실제 회원가입 스킨: `mobile/skin/member/basic/register_form.skin.php`
- 테마 회원 스킨 CSS: `theme/willow_mobile/mobile/skin/member/basic/style.css`
- 테마 로그인 CSS: `theme/willow_mobile/mobile/skin/member/basic/willow_auth.css`
- CSS/JS 버전: `extend/version.extend.php`

## 검증 팁

운영 PC 레이아웃은 Chrome headless를 로컬 Chrome 실행 파일로 열어 확인할 수 있다.

```bash
NODE_PATH=/Users/aiden/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules \
/Users/aiden/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node <script>
```

최근 배포 상태를 빠르게 확인하는 명령:

```bash
curl -s 'https://willlow.org/bbs/login.php?url=%2F' | rg -n 'willow_auth|willow_auth.css|2610113' -m 8
curl -s 'https://willlow.org/theme/willow_mobile/css/willow_splash.css?ver=2610113' | rg -n 'cursor: grab|cursor: grabbing'
curl -s 'https://willlow.org/willow/splash.php' | rg -n 'pointerdown|pointermove|pointerup|beginDrag'
```

최근 PC 프레임 점검 대상 URL:

- `https://willlow.org/`
- `https://willlow.org/willow/menu.php`
- `https://willlow.org/willow/post.php?wp_id=41`
- `https://willlow.org/bbs/member_confirm.php?url=register_form.php`
- `https://willlow.org/bbs/register_form.php`
- `https://willlow.org/bbs/login.php?url=%2F`
- `https://willlow.org/willow/splash.php`

스플래시를 건너뛰어 홈 화면을 보고 싶으면 브라우저 쿠키 `md5('willow_splash_seen')` 값이 필요하다.
현재 키 이름은 `e58557c01b28e768812fe13354c79b0f`다.
