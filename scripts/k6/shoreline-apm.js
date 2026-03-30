import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { parseHTML } from 'k6/html';

const BASE_URL = (__ENV.BASE_URL || 'https://shoreline.my.id').replace(/\/+$/, '');
const SCENARIO = (__ENV.SCENARIO || 'light').toLowerCase();

const USER_EMAIL = __ENV.USER_EMAIL || '';
const USER_PASSWORD = __ENV.USER_PASSWORD || '';
const USER_ACCOUNTS = __ENV.USER_ACCOUNTS || '';

const ADMIN_EMAIL = __ENV.ADMIN_EMAIL || '';
const ADMIN_PASSWORD = __ENV.ADMIN_PASSWORD || '';
const ADMIN_ACCOUNTS = __ENV.ADMIN_ACCOUNTS || '';

const ADDRESS_ID = __ENV.ADDRESS_ID || '';
const COURIER = (__ENV.COURIER || 'jne').toLowerCase();

const THINK_TIME = Number(__ENV.THINK_TIME || 1);

let customerSessionReady = false;
let adminSessionReady = false;

function parseAccounts(rawAccounts, withAddressId = false) {
    if (!rawAccounts.trim()) {
        return [];
    }

    return rawAccounts.split(/[;\n]+/)
        .map((entry) => entry.trim())
        .filter(Boolean)
        .map((entry) => {
            const [email = '', password = '', addressId = ''] = entry.split('|').map((part) => part.trim());
            const account = { email, password };

            if (withAddressId) {
                account.addressId = addressId;
            }

            return account;
        })
        .filter((account) => account.email && account.password);
}

const CUSTOMER_ACCOUNTS = parseAccounts(USER_ACCOUNTS, true);
const ADMIN_ACCOUNT_LIST = parseAccounts(ADMIN_ACCOUNTS);

function buildOptions() {
    const presets = {
        light: { vus: 5, duration: '5m', exec: 'customerFlow' },
        medium: { vus: 10, duration: '10m', exec: 'customerFlow' },
        heavy: { vus: 20, duration: '10m', exec: 'customerFlow' },
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

function isLoginPage(response) {
    const body = htmlBody(response);

    return (
        body.includes('<form method="POST" action=') &&
        body.includes('name="email"') &&
        body.includes('name="password"')
    );
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

function randomArrayItem(items) {
    if (!items || items.length === 0) {
        return null;
    }

    const index = Math.floor(Math.random() * items.length);
    return items[index] || null;
}

function firstRegexMatch(response, regex) {
    const body = htmlBody(response);
    const match = body.match(regex);

    return match ? match[1] : '';
}

function extractProductId(response) {
    const value = firstRegexMatch(response, /name="produk_id"\s+value="(\d+)"/i);
    return value ? Number(value) : 0;
}

function extractAvailableVariantId(response) {
    const raw = firstRegexMatch(response, /const varians = (\[[\s\S]*?\]);/i);

    if (!raw) {
        return 0;
    }

    try {
        const variants = JSON.parse(raw);
        const availableVariants = variants.filter((variant) => Number(variant.stok || 0) > 0);
        const available = randomArrayItem(availableVariants);

        return available ? Number(available.id || 0) : 0;
    } catch (_error) {
        return 0;
    }
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
    const doc = parseHTML(htmlBody(productsResponse));
    const productPaths = doc
        .find('a.product-item')
        .toArray()
        .map((link) => link.attr('href'))
        .filter(Boolean);

    let detailPath = randomArrayItem(productPaths);

    if (!detailPath) {
        const regexMatches = Array.from(
            htmlBody(productsResponse).matchAll(/href="([^"]*\/produk\/[^"]+)"/gi)
        )
            .map((match) => match[1])
            .filter(Boolean);

        detailPath = randomArrayItem(regexMatches) || '';
    }

    if (!detailPath) {
        return null;
    }

    const res = http.get(url(detailPath), defaultParams('GET /produk/{produk}'));
    assertOk(res, 'detail produk');
    return { response: res, path: detailPath };
}

function addToCart(productDetailResponse) {
    const csrf = extractCsrfToken(productDetailResponse);
    const productId = extractProductId(productDetailResponse);
    const variantId = extractAvailableVariantId(productDetailResponse);

    check(productId, {
        'produk id ditemukan di detail produk': (value) => value > 0,
    });
    check(variantId, {
        'varian tersedia ditemukan di detail produk': (value) => value > 0,
    });

    if (!csrf || !productId || !variantId) {
        return null;
    }

    const res = http.post(
        url('/keranjang'),
        {
            _token: csrf,
            produk_id: productId,
            varian_id: variantId,
            jumlah_produk: 1,
        },
        {
            tags: { name: 'POST /keranjang' },
            headers: {
                Accept: 'text/html,application/xhtml+xml,application/json',
            },
            redirects: 10,
        }
    );

    check(res, {
        'tambah ke keranjang berhasil': (r) => r.status < 400 && !isLoginPage(r),
    });

    return res;
}

function clearCart() {
    const cartPage = http.get(url('/keranjang'), defaultParams('GET /keranjang'));

    check(cartPage, {
        'keranjang terbuka': (r) => r.status < 400,
        'keranjang session valid': (r) => !isLoginPage(r),
    });

    if (isLoginPage(cartPage)) {
        return false;
    }

    const csrf = extractCsrfToken(cartPage);
    const doc = parseHTML(htmlBody(cartPage));
    const deleteActions = doc
        .find('form[action*="/keranjang/"]')
        .toArray()
        .map((form) => {
            const method = form.find('input[name="_method"]').attr('value');
            const action = form.attr('action');

            if ((method || '').toUpperCase() !== 'DELETE') {
                return '';
            }

            return action || '';
        })
        .filter(Boolean);

    for (const action of deleteActions) {
        const response = http.post(
            url(action),
            {
                _token: csrf,
                _method: 'DELETE',
            },
            {
                tags: { name: 'DELETE /keranjang/{id}' },
                headers: {
                    Accept: 'text/html,application/xhtml+xml,application/json',
                },
                redirects: 10,
            }
        );

        check(response, {
            'hapus item keranjang berhasil': (r) => r.status < 400 && !isLoginPage(r),
        });
    }

    return true;
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
            r.status < 400 && !isLoginPage(r),
    });

    return res.status < 400 && !isLoginPage(res);
}

function currentCustomerAccount() {
    if (CUSTOMER_ACCOUNTS.length > 0) {
        return CUSTOMER_ACCOUNTS[(__VU - 1) % CUSTOMER_ACCOUNTS.length];
    }

    return {
        email: USER_EMAIL,
        password: USER_PASSWORD,
        addressId: ADDRESS_ID,
    };
}

function currentAdminAccount() {
    if (ADMIN_ACCOUNT_LIST.length > 0) {
        return ADMIN_ACCOUNT_LIST[(__VU - 1) % ADMIN_ACCOUNT_LIST.length];
    }

    return {
        email: ADMIN_EMAIL,
        password: ADMIN_PASSWORD,
    };
}

function visitCheckout() {
    const res = http.get(url('/checkout'), defaultParams('GET /checkout'));
    check(res, {
        'checkout terbuka dan session valid': (r) => r.status < 400 && !isLoginPage(r),
    });
    return res;
}

function hitShippingOptions() {
    const account = currentCustomerAccount();
    const addressId = account.addressId || ADDRESS_ID;

    if (!addressId) {
        return null;
    }

    const res = http.post(
        url('/checkout/shipping-options'),
        {
            alamat_id: addressId,
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
        'pesanan terbuka dan session valid': (r) => r.status < 400 && !isLoginPage(r),
    });
    return res;
}

function extractOrderDetailPath(ordersResponse) {
    const doc = parseHTML(htmlBody(ordersResponse));
    const detailLinks = doc
        .find('a[href*="/pesanan/"]')
        .toArray()
        .map((link) => ({
            href: link.attr('href') || '',
            text: link.text().trim().toLowerCase(),
        }))
        .filter((link) => {
            return (
                /^\/pesanan\/\d+(?:#refund)?$/i.test(link.href) &&
                !link.href.includes('#refund') &&
                link.text.includes('detail')
            );
        })
        .map((link) => link.href);

    return randomArrayItem(detailLinks) || '';
}

function visitOrderDetail(ordersResponse) {
    const detailPath = extractOrderDetailPath(ordersResponse);

    if (!detailPath) {
        return null;
    }

    const res = http.get(url(detailPath), defaultParams('GET /pesanan/{transaksi}'));
    check(res, {
        'detail pesanan status < 400': (r) => r.status < 400,
        'detail pesanan bukan halaman login': (r) => !isLoginPage(r),
        'detail pesanan memuat judul': (r) => htmlBody(r).includes('Detail Pesanan'),
    });

    return { response: res, path: detailPath };
}

function ensureCustomerSession(force = false) {
    const account = currentCustomerAccount();

    if (customerSessionReady && !force) {
        return true;
    }

    customerSessionReady = login(account.email, account.password, `customer ${account.email}`);
    return customerSessionReady;
}

function ensureAdminSession() {
    const account = currentAdminAccount();

    if (adminSessionReady) {
        return true;
    }

    adminSessionReady = login(account.email, account.password, `admin ${account.email}`);
    return adminSessionReady;
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

function visitAdminTransactions() {
    const res = http.get(url('/admin/transaksi'), defaultParams('GET /admin/transaksi'));
    check(res, {
        'admin transaksi terbuka': (r) => r.status < 400,
    });
    return res;
}

function visitAdminPayments() {
    const res = http.get(url('/admin/pembayaran'), defaultParams('GET /admin/pembayaran'));
    check(res, {
        'admin pembayaran terbuka': (r) => r.status < 400,
    });
    return res;
}

function visitAdminCustomers() {
    const res = http.get(url('/admin/customer'), defaultParams('GET /admin/customer'));
    check(res, {
        'admin customer terbuka': (r) => r.status < 400,
    });
    return res;
}

function visitAdminProducts() {
    const res = http.get(url('/admin/produk'), defaultParams('GET /admin/produk'));
    check(res, {
        'admin produk terbuka': (r) => r.status < 400,
    });
    return res;
}

function userJourney() {
    let productDetail = null;

    group('Public pages', function () {
        visitHome();
        sleep(THINK_TIME);

        const produk = visitProducts();
        sleep(THINK_TIME);

        productDetail = visitProductDetail(produk);
        sleep(THINK_TIME);
    });

    const account = currentCustomerAccount();

    if (!account.email || !account.password) {
        return;
    }

    group('Customer pages', function () {
        const loggedIn = ensureCustomerSession();
        sleep(THINK_TIME);

        if (!loggedIn) {
            return;
        }

        const cartReady = clearCart();
        sleep(THINK_TIME);

        if (cartReady && productDetail?.path) {
            const authenticatedProductDetail = http.get(
                url(productDetail.path),
                defaultParams('GET /produk/{produk} authenticated')
            );
            assertOk(authenticatedProductDetail, 'detail produk setelah login');

            addToCart(authenticatedProductDetail);
            sleep(THINK_TIME);
        }

        let checkout = visitCheckout();
        if (isLoginPage(checkout)) {
            customerSessionReady = false;
            if (!ensureCustomerSession(true)) {
                return;
            }
            checkout = visitCheckout();
        }
        sleep(THINK_TIME);

        hitShippingOptions();
        sleep(THINK_TIME);

        let orders = visitOrders();
        if (isLoginPage(orders)) {
            customerSessionReady = false;
            if (!ensureCustomerSession(true)) {
                return;
            }
            orders = visitOrders();
        }
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
        const loggedIn = ensureAdminSession();
        sleep(THINK_TIME);

        if (!loggedIn) {
            return;
        }

        visitAdminDashboard();
        sleep(THINK_TIME);

        visitAdminTransactions();
        sleep(THINK_TIME);

        visitAdminPayments();
        sleep(THINK_TIME);

        visitAdminCustomers();
        sleep(THINK_TIME);

        visitAdminProducts();
        sleep(THINK_TIME);

        visitRevenueReport();
        sleep(THINK_TIME);
    });
}
