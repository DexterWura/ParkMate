<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Shipit') }} &middot; Laravel Boilerplate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --bg-soft: #12121a;
            --card: #16161f;
            --border: #24242f;
            --text: #e7e7ef;
            --muted: #9a9aae;
            --brand: #7c5cff;
            --brand-2: #4dd0e1;
            --green: #34d399;
            --amber: #fbbf24;
            --pink: #fb7185;
            --radius: 14px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: radial-gradient(1200px 800px at 80% -10%, rgba(124,92,255,.18), transparent 60%),
                        radial-gradient(900px 600px at 0% 10%, rgba(77,208,225,.10), transparent 55%),
                        var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        code, pre { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 0 24px; }

        /* Nav */
        nav {
            position: sticky; top: 0; z-index: 50;
            backdrop-filter: blur(12px);
            background: rgba(10,10,15,.7);
            border-bottom: 1px solid var(--border);
        }
        nav .wrap { display: flex; align-items: center; justify-content: space-between; height: 64px; }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; letter-spacing: -.02em; }
        .logo .dot { width: 26px; height: 26px; border-radius: 8px; background: linear-gradient(135deg, var(--brand), var(--brand-2)); box-shadow: 0 0 22px rgba(124,92,255,.6); }
        .nav-links { display: flex; gap: 26px; font-size: 14px; color: var(--muted); }
        .nav-links a:hover { color: var(--text); }
        @media (max-width: 720px) { .nav-links { display: none; } }

        /* Hero */
        header { padding: 90px 0 60px; text-align: center; }
        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px; border: 1px solid var(--border); border-radius: 999px;
            background: var(--bg-soft); font-size: 13px; color: var(--muted); margin-bottom: 26px;
        }
        .badge .pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--green); box-shadow: 0 0 10px var(--green); }
        h1 { font-size: clamp(38px, 6vw, 64px); font-weight: 800; letter-spacing: -.03em; line-height: 1.05; }
        h1 .grad { background: linear-gradient(120deg, var(--brand) 20%, var(--brand-2)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        header p { max-width: 620px; margin: 22px auto 0; color: var(--muted); font-size: 18px; }
        .cta { display: flex; gap: 14px; justify-content: center; margin-top: 34px; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 10px;
            font-weight: 600; font-size: 15px; transition: transform .15s, box-shadow .15s, background .15s; cursor: pointer;
        }
        .btn-primary { background: linear-gradient(135deg, var(--brand), #6a4bff); color: #fff; box-shadow: 0 8px 30px rgba(124,92,255,.35); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 38px rgba(124,92,255,.5); }
        .btn-ghost { background: var(--card); border: 1px solid var(--border); color: var(--text); }
        .btn-ghost:hover { border-color: var(--brand); }

        /* Sections */
        section { padding: 56px 0; }
        .eyebrow { color: var(--brand-2); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: .12em; }
        h2 { font-size: 30px; font-weight: 700; letter-spacing: -.02em; margin: 8px 0 8px; }
        .section-sub { color: var(--muted); margin-bottom: 30px; max-width: 640px; }

        /* Feature grid */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; }
        .card {
            background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 22px; transition: transform .15s, border-color .15s;
        }
        .card:hover { transform: translateY(-3px); border-color: rgba(124,92,255,.5); }
        .card .ic { width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center; font-size: 20px; margin-bottom: 14px; background: rgba(124,92,255,.12); }
        .card h3 { font-size: 17px; margin-bottom: 6px; }
        .card p { color: var(--muted); font-size: 14px; }

        /* Steps */
        .steps { display: grid; gap: 16px; }
        .step { display: grid; grid-template-columns: 44px 1fr; gap: 18px; align-items: start; }
        .step .n { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; font-weight: 700; background: linear-gradient(135deg, rgba(124,92,255,.25), rgba(77,208,225,.15)); border: 1px solid var(--border); }
        .step h4 { font-size: 16px; margin-bottom: 8px; }

        /* Code block */
        pre {
            background: #0d0d14; border: 1px solid var(--border); border-radius: 12px;
            padding: 18px 20px; overflow-x: auto; font-size: 13.5px; line-height: 1.7; margin-top: 10px;
        }
        pre .c { color: #6b7280; }
        pre .k { color: var(--brand-2); }
        pre .s { color: var(--green); }

        /* Endpoints table */
        .table { width: 100%; border-collapse: collapse; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .table th, .table td { text-align: left; padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .table th { background: var(--bg-soft); color: var(--muted); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
        .table tr:last-child td { border-bottom: none; }
        .table td code { font-size: 13px; color: var(--text); }
        .m { display: inline-block; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; letter-spacing: .04em; }
        .m-get { background: rgba(52,211,153,.15); color: var(--green); }
        .m-post { background: rgba(124,92,255,.18); color: #b3a1ff; }
        .lock { color: var(--amber); font-size: 12px; }

        /* Gateways */
        .gw { display: flex; align-items: center; gap: 14px; }
        .gw .pill { padding: 4px 10px; border-radius: 999px; font-size: 12px; border: 1px solid var(--border); background: var(--bg-soft); color: var(--muted); }

        /* Tree */
        .tree { background: #0d0d14; border: 1px solid var(--border); border-radius: 12px; padding: 20px 22px; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: var(--muted); line-height: 1.75; overflow-x: auto; }
        .tree .d { color: var(--brand-2); }

        footer { padding: 50px 0; border-top: 1px solid var(--border); color: var(--muted); font-size: 14px; text-align: center; }
        footer a { color: var(--brand-2); }
    </style>
</head>
<body>
    <nav>
        <div class="wrap">
            <div class="logo"><span class="dot"></span> {{ config('app.name', 'Shipit') }}</div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#start">Get Started</a>
                <a href="#auth">Auth</a>
                <a href="#payments">Payments</a>
                <a href="#api">API</a>
                <a href="#structure">Structure</a>
            </div>
        </div>
    </nav>

    <header>
        <div class="wrap">
            <span class="badge"><span class="pulse"></span> Laravel 13 &middot; Domain-Driven Boilerplate</span>
            <h1>Ship production apps<br><span class="grad">without the boilerplate.</span></h1>
            <p>A stable, domain-driven Laravel starter with rock-solid auth, Google sign-in, and multi-gateway payments &mdash; Stripe, Paynow &amp; Pesepay &mdash; ready out of the box.</p>
            <div class="cta">
                <a class="btn btn-primary" href="#start">Get Started &rarr;</a>
                <a class="btn btn-ghost" href="#api">View API Reference</a>
            </div>
        </div>
    </header>

    <section id="features">
        <div class="wrap">
            <span class="eyebrow">Everything included</span>
            <h2>Batteries-included foundation</h2>
            <p class="section-sub">Each concern lives in its own domain module, so features stay isolated, testable, and easy to scale.</p>
            <div class="grid">
                <div class="card"><div class="ic">🔐</div><h3>Stable Auth</h3><p>Sanctum tokens, email verification, password hashing, and policy-based authorization.</p></div>
                <div class="card"><div class="ic">🟢</div><h3>Google Sign-In</h3><p>OAuth via Laravel Socialite with linked social accounts and auto user provisioning.</p></div>
                <div class="card"><div class="ic">💳</div><h3>Multi-Gateway Payments</h3><p>Stripe, Paynow &amp; Pesepay behind one clean interface. Swap gateways per request.</p></div>
                <div class="card"><div class="ic">🧱</div><h3>Domain-Driven</h3><p>Controllers, Services, Repositories, Actions, DTOs &amp; Events per domain.</p></div>
                <div class="card"><div class="ic">🔁</div><h3>Subscriptions</h3><p>Cashier-powered plans, checkout, billing portal, and webhook handling.</p></div>
                <div class="card"><div class="ic">🧩</div><h3>Infrastructure Layer</h3><p>Swappable wrappers for Cache, Queue, Mail, Storage, Search &amp; External APIs.</p></div>
            </div>
        </div>
    </section>

    <section id="start">
        <div class="wrap">
            <span class="eyebrow">Quickstart</span>
            <h2>Get started in minutes</h2>
            <p class="section-sub">Requires PHP 8.3+ and Composer. The first-run installer configures the environment and database for you.</p>
            <div class="steps">
                <div class="step"><div class="n">1</div><div><h4>Install dependencies</h4>
<pre>composer install</pre></div></div>
                <div class="step"><div class="n">2</div><div><h4>Serve the app</h4>
<pre>php artisan serve
<span class="c"># then open</span> http://localhost:8000</pre></div></div>
                <div class="step"><div class="n">3</div><div><h4>Complete setup</h4>
<pre><span class="c"># /install creates .env, runs Laravel migrations,</span>
<span class="c"># and creates your first verified account.</span></pre></div></div>
            </div>
        </div>
    </section>

    <section id="auth">
        <div class="wrap">
            <span class="eyebrow">Authentication</span>
            <h2>Email &amp; Google sign-in</h2>
            <p class="section-sub">Register with email/password or continue with Google. Configure your OAuth client, then point the redirect URI to the callback below.</p>
            <pre><span class="c"># .env</span>
GOOGLE_CLIENT_ID=<span class="s">your-client-id</span>
GOOGLE_CLIENT_SECRET=<span class="s">your-client-secret</span>
GOOGLE_REDIRECT_URI=<span class="s">http://localhost:8000/api/v1/auth/social/google/callback</span></pre>
            <table class="table" style="margin-top:22px">
                <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/v1/auth/social/google/redirect</code></td><td>Redirect to Google consent screen</td></tr>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/v1/auth/social/google/callback</code></td><td>Handle callback, issue Sanctum token</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="payments">
        <div class="wrap">
            <span class="eyebrow">Payments</span>
            <h2>One interface, three gateways</h2>
            <p class="section-sub">Every processor implements <code>PaymentProcessorInterface</code>. Pick a gateway per request and get back a redirect or poll URL.</p>
            <div class="grid" style="margin-bottom:24px">
                <div class="card"><div class="gw"><h3>Stripe</h3><span class="pill">Cards &middot; Global</span></div><p style="margin-top:10px">Checkout Sessions &amp; subscriptions via Cashier.</p></div>
                <div class="card"><div class="gw"><h3>Paynow</h3><span class="pill">EcoCash &middot; ZW</span></div><p style="margin-top:10px">Web redirect &amp; mobile money express checkout.</p></div>
                <div class="card"><div class="gw"><h3>Pesepay</h3><span class="pill">Multi &middot; ZW</span></div><p style="margin-top:10px">Encrypted payload initiation &amp; status polling.</p></div>
            </div>
            <pre><span class="c"># initiate a payment</span>
POST /api/v1/payments/initiate
{
  <span class="k">"gateway"</span>: <span class="s">"paynow"</span>,   <span class="c">// stripe | paynow | pesepay</span>
  <span class="k">"amount"</span>: <span class="s">10.00</span>,
  <span class="k">"currency"</span>: <span class="s">"USD"</span>,
  <span class="k">"email"</span>: <span class="s">"customer@example.com"</span>,
  <span class="k">"phone"</span>: <span class="s">"0771234567"</span>,   <span class="c">// mobile money (optional)</span>
  <span class="k">"method"</span>: <span class="s">"ecocash"</span>,      <span class="c">// optional</span>
  <span class="k">"description"</span>: <span class="s">"Pro plan"</span>
}</pre>
        </div>
    </section>

    <section id="api">
        <div class="wrap">
            <span class="eyebrow">Reference</span>
            <h2>API endpoints</h2>
            <p class="section-sub">All responses follow a consistent <code>{ success, message, data }</code> envelope. Protected routes require a Sanctum bearer token.</p>
            <table class="table">
                <thead><tr><th>Method</th><th>Endpoint</th><th>Auth</th></tr></thead>
                <tbody>
                    <tr><td><span class="m m-post">POST</span></td><td><code>/api/v1/auth/register</code></td><td>Public</td></tr>
                    <tr><td><span class="m m-post">POST</span></td><td><code>/api/v1/auth/login</code></td><td>Public</td></tr>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/v1/auth/social/google/redirect</code></td><td>Public</td></tr>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/v1/auth/social/google/callback</code></td><td>Public</td></tr>
                    <tr><td><span class="m m-post">POST</span></td><td><code>/api/v1/auth/logout</code></td><td><span class="lock">🔒 Sanctum</span></td></tr>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/v1/auth/me</code></td><td><span class="lock">🔒 Sanctum</span></td></tr>
                    <tr><td><span class="m m-post">POST</span></td><td><code>/api/v1/payments/initiate</code></td><td><span class="lock">🔒 Sanctum</span></td></tr>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/v1/payments/{reference}/status</code></td><td><span class="lock">🔒 Sanctum</span></td></tr>
                    <tr><td><span class="m m-post">POST</span></td><td><code>/api/v1/billing/checkout</code></td><td><span class="lock">🔒 Sanctum</span></td></tr>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/v1/billing/subscription</code></td><td><span class="lock">🔒 Sanctum</span></td></tr>
                    <tr><td><span class="m m-get">GET</span></td><td><code>/api/health</code></td><td>Public</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="structure">
        <div class="wrap">
            <span class="eyebrow">Architecture</span>
            <h2>Folder structure</h2>
            <p class="section-sub">Domain-driven layout that keeps business logic isolated and the framework at the edges.</p>
            <div class="tree">app/
├── <span class="d">Core/</span>          <span class="c"># Auth, Exceptions, Providers, Helpers</span>
├── <span class="d">Domains/</span>       <span class="c"># User, Billing, Subscription, SocialAccounts, ...</span>
│   └── <span class="d">User/</span>       <span class="c"># Controllers, Services, Repositories, Actions, DTOs</span>
├── <span class="d">Shared/</span>        <span class="c"># Contracts, DTOs, ValueObjects, Traits</span>
└── <span class="d">Infrastructure/</span><span class="c"># Cache, Queue, Mail, ExternalApis (gateways)</span></div>
        </div>
    </section>

    <footer>
        <div class="wrap">
            Built with <a href="https://laravel.com">Laravel {{ app()->version() }}</a> &middot; {{ config('app.name', 'Shipit') }} Boilerplate &middot; {{ date('Y') }}
        </div>
    </footer>
</body>
</html>
