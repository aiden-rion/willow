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

- PC 화면에서는 모바일 UI를 `390px` 프레임으로 보여주고 배경에는 WILLOW 소개/검색 영역을 배치한다.
- PC 배경 오른쪽 소개 영역은 제거했고, 왼쪽에 로고/카피/글 검색창/주요 키워드 칩을 배치했다.
- 모바일 프레임 내부 콘텐츠가 PC에서 오른쪽으로 삐져나가지 않도록 `.willow_app`, 카드, 배너, 추천영역의 폭을 프레임 내부 기준으로 제한했다.
- CSS 캐시는 `extend/version.extend.php`의 `G5_CSS_VER`를 올려 갱신한다.

## 자주 확인하는 파일

- 공통 모바일 헤더: `theme/willow_mobile/mobile/head.php`
- 공통 모바일 푸터: `theme/willow_mobile/mobile/tail.php`
- 메인 홈: `theme/willow_mobile/mobile/index.php`
- 스플래시: `willow/splash.php`
- 모바일 공통 CSS: `theme/willow_mobile/css/willow_mobile.css`
- 스플래시 CSS: `theme/willow_mobile/css/willow_splash.css`
- 콘텐츠/글 상세 CSS: `theme/willow_mobile/css/willow_content.css`
- CSS/JS 버전: `extend/version.extend.php`

## 검증 팁

운영 PC 레이아웃은 Chrome headless를 로컬 Chrome 실행 파일로 열어 확인할 수 있다.

```bash
NODE_PATH=/Users/aiden/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules \
/Users/aiden/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node <script>
```

스플래시를 건너뛰어 홈 화면을 보고 싶으면 브라우저 쿠키 `md5('willow_splash_seen')` 값이 필요하다.
현재 키 이름은 `e58557c01b28e768812fe13354c79b0f`다.
