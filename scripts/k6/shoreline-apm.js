import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { parseHTML } from 'k6/html';

const BASE_URL = (__ENV.BASE_URL || 'https://shoreline.my.id').replace(/\/+$/, '');
const SCENARIO = (__ENV.SCENARIO || 'light').toLowerCase();

const USER_EMAIL = __ENV.USER_EMAIL || '';
const USER_PASSWORD = __ENV.USER_PASSWORD || '';

const ADMIN_EMAIL = __ENV.ADMIN_EMAIL || '';
const ADMIN_PASSWORD = __ENV.ADMIN_PASSWORD || '';

const ADDRESS_ID = __ENV.ADDRESS_ID || '';
const COURIER = (__ENV.COURIER || 'jne').toLowerCase();

const THINK_TIME = Number(__ENV.THINK_TIME || 1);

function buildOptions() {
    const presets = {
        light: { vus: 5, duration: '5m', exec: 'customerFlow' },
        medium: { vus: 10, duration: '10m', exec: 'customerFlow' },
        heavy: { vus: 25, duration: '10m', exec: 'customerFlow' },
        admin: { vus: 3, duration: '5m', exec: 'adminFlow' },
        public: { vus: 5, duration: '5m', exec: 'publicFlow' },
    };

    const selected = presets[SCENARIO] || presets.light;

    return {
        scenarios: {
            [SCENARIO]: {
                executor: 'constant-vus',
                vus: selected.vus,
                duration: selected.duration,
                exec: selected.exec,
            },
        },
        thresholds: {
            http_req_failed: ['rate<0.10'],
            http_req_duration: ['p(95)<2000'],
        },
    };
}

export const options = buildOptions();

function url(path) {
    if (!path) {
        return BASE_URL;
    }

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    return `${BASE_URL}${path.startsWith('/') ? '' : '/'}${path}`;
}

function defaultParams(name) {
    return {
        tags: { name },
        headers: {
            Accept: 'text/html,application/xhtml+xml,application/json',
        },
    };
}

function jsonParams(name) {
    return {
        tags: { name },
        headers: {
            Accept: 'application/json',
        },
    };
}

function htmlBody(response) {
    return typeof response.body === 'string' ? response.body : '';
}

function extractCsrfToken(response) {
    const doc = parseHTML(htmlBody(response));
    const token = doc.find('input[name="_token"]').first().attr('value');

    if (token) {
        return token;
    }

    const body = htmlBody(response);
    const match = body.match(/name="_token"\s+value="([^"]+)"/i);

    return match ? match[1] : '';
}

function firstHrefBySelector(response, selector) {
    const doc = parseHTML(htmlBody(response));
    const href = doc.find(selector).first().attr('href');

    return href || '';
}

function firstRegexMatch(response, regex) {
    const body = htmlBody(response);
    const match = body.match(regex);

    return match ? match[1] : '';
}

function assertOk(response, label) {
    check(response, {
        [`${label} status < 400`]: (r) => r.status < 400,
    });
}

function visitHome() {
    const res = http.get(url('/'), defaultParams('GET /'));
    assertOk(res, 'home');
    return res;
}

function visitProducts() {
    const res = http.get(url('/produk'), defaultParams('GET /produk'));
    assertOk(res, 'produk');
    return res;
}

function visitProductDetail(productsResponse) {
    const detailPath =
        firstHrefBySelector(productsResponse, 'a.product-item') ||
        firstRegexMatch(productsResponse, /href="([^"]*\/produk\/[^"]+)"/i);

    if (!detailPath) {
        return null;
    }

    const res = http.get(url(detailPath), defaultParams('GET /produk/{produk}'));
    assertOk(res, 'detail produk');
    return { response: res, path: detailPath };
}

function login(email, password, label) {
    if (!email || !password) {
        return false;
    }

    const loginPage = http.get(url('/login'), defaultParams('GET /login'));
    assertOk(loginPage, `${label} login page`);

    const csrf = extractCsrfToken(loginPage);
    check(csrf, {
        [`${label} csrf token ditemukan`]: (value) => value !== '',
    });

    const payload = {
        _token: csrf,
        email,
        password,
    };

    const res = http.post(url('/login'), payload, {
        tags: { name: 'POST /login' },
        headers: {
            Accept: 'text/html,application/xhtml+xml,application/json',
        },
        redirects: 10,
    });

    check(res, {
        [`${label} login berhasil`]: (r) =>
            r.status < 400 && !htmlBody(r).includes('name="email"'),
    });

    return res.status < 400;
}

function visitCheckout() {
    const res = http.get(url('/checkout'), defaultParams('GET /checkout'));
    check(res, {
        'checkout terbuka atau redirect valid': (r) => r.status < 400,
    });
    return res;
}

function hitShippingOptions() {
    if (!ADDRESS_ID) {
        return null;
    }

    const res = http.post(
        url('/checkout/shipping-options'),
        {
            alamat_id: ADDRESS_ID,
            courier: COURIER,
        },
        jsonParams('POST /checkout/shipping-options')
    );

    check(res, {
        'shipping options merespons': (r) => r.status < 500,
    });

    return res;
}

function visitOrders() {
    const res = http.get(url('/pesanan'), defaultParams('GET /pesanan'));
    check(res, {
        'pesanan terbuka atau redirect valid': (r) => r.status < 400,
    });
    return res;
}

function visitOrderDetail(ordersResponse) {
    const detailPath =
        firstHrefBySelector(ordersResponse, 'a[href*="/pesanan/"]') ||
        firstRegexMatch(ordersResponse, /href="([^"]*\/pesanan\/\d+[^"]*)"/i);

    if (!detailPath) {
        return null;
    }

    const res = http.get(url(detailPath), defaultParams('GET /pesanan/{transaksi}'));
    assertOk(res, 'detail pesanan');

    return { response: res, path: detailPath };
}

function hitOrderStatus(orderDetailResponse, orderDetailPath) {
    const explicitTrackUrl = firstRegexMatch(
        orderDetailResponse,
        /data-track-url="([^"]+)"/i
    );

    const basePath = orderDetailPath.replace(/#.*$/, '').replace(/\/$/, '');
    const statusPath = explicitTrackUrl ? '' : `${basePath}/status`;

    const res = http.get(
        url(explicitTrackUrl || statusPath),
        jsonParams(explicitTrackUrl ? 'GET tracking-json' : 'GET /pesanan/{transaksi}/status')
    );

    check(res, {
        'status/tracking pesanan merespons': (r) => r.status < 500,
    });

    return res;
}

function visitAdminDashboard() {
    const res = http.get(url('/admin/dashboard'), defaultParams('GET /admin/dashboard'));
    check(res, {
        'admin dashboard terbuka atau redirect valid': (r) => r.status < 400,
    });
    return res;
}

function visitRevenueReport() {
    const desc = http.get(
        url('/admin/laporan/pendapatan?sort=desc'),
        defaultParams('GET /admin/laporan/pendapatan desc')
    );
    check(desc, {
        'laporan pendapatan desc terbuka': (r) => r.status < 400,
    });

    const asc = http.get(
        url('/admin/laporan/pendapatan?sort=asc'),
        defaultParams('GET /admin/laporan/pendapatan asc')
    );
    check(asc, {
        'laporan pendapatan asc terbuka': (r) => r.status < 400,
    });

    return { desc, asc };
}

function userJourney() {
    group('Public pages', function () {
        visitHome();
        sleep(THINK_TIME);

        const produk = visitProducts();
        sleep(THINK_TIME);

        visitProductDetail(produk);
        sleep(THINK_TIME);
    });

    if (!USER_EMAIL || !USER_PASSWORD) {
        return;
    }

    group('Customer pages', function () {
        const loggedIn = login(USER_EMAIL, USER_PASSWORD, 'customer');
        sleep(THINK_TIME);

        if (!loggedIn) {
            return;
        }

        visitCheckout();
        sleep(THINK_TIME);

        hitShippingOptions();
        sleep(THINK_TIME);

        const orders = visitOrders();
        sleep(THINK_TIME);

        const orderDetail = visitOrderDetail(orders);
        sleep(THINK_TIME);

        if (orderDetail) {
            hitOrderStatus(orderDetail.response, orderDetail.path);
        }
    });
}

export function publicFlow() {
    userJourney();
}

export function customerFlow() {
    userJourney();
}

export function adminFlow() {
    group('Admin login and pages', function () {
        const loggedIn = login(ADMIN_EMAIL, ADMIN_PASSWORD, 'admin');
        sleep(THINK_TIME);

        if (!loggedIn) {
            return;
        }

        visitAdminDashboard();
        sleep(THINK_TIME);

        visitRevenueReport();
        sleep(THINK_TIME);
    });
}

