<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Campus-Lost-and-Found-Asset-Recovery-Portal helps your campus report, match, verify, and return lost belongings.">
    <title>Campus Lost and Found Asset Recovery Portal | Lost belongings, found together</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="landing-page">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="brand-mark" aria-hidden="true">C</span>
                <span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="#features">Features</a>
                    <a class="nav-link" href="#how-it-works">How it works</a>
                    <a class="nav-link" href="login.php">Sign in</a>
                    <a class="nav-link" href="register.php">Create account</a>
                    <a class="btn btn-primary btn-sm px-3 ms-lg-2" href="report.php">Report an item</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="landing-hero">
            <div class="container py-5 py-lg-6">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7">
                        <span class="hero-label"><span class="hero-label-dot"></span> A campus community that cares</span>
                        <h1 class="display-3 fw-bold mt-4 mb-3">Lost belongings.<br><span>Found together.</span></h1>
                        <p class="hero-copy mb-4">
                            A safer, simpler way for students and staff to report missing items, find possible matches,
                            and get belongings back to their owners.
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <a class="btn btn-primary btn-lg px-4" href="report.php">Report a lost or found item <span aria-hidden="true">&rarr;</span></a>
                            <a class="btn btn-outline-secondary btn-lg px-4" href="#features">Explore the features</a>
                        </div>
                        <p class="small text-secondary mt-3 mb-0">Sign in to submit a report. Recovery tools are being built in stages.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="journey-card">
                            <div class="journey-card-top">
                                <div>
                                    <span class="journey-kicker">THE RECOVERY JOURNEY</span>
                                    <h2 class="h5 fw-bold mt-2 mb-0">From missing to back where it belongs</h2>
                                </div>
                                <span class="journey-spark" aria-hidden="true">+</span>
                            </div>
                            <ol class="journey-list">
                                <li class="journey-step is-current">
                                    <span class="journey-number">01</span>
                                    <div><strong>Report</strong><span>Share item details and where it was lost or found.</span></div>
                                    <span class="step-tag">START HERE</span>
                                </li>
                                <li class="journey-step">
                                    <span class="journey-number">02</span>
                                    <div><strong>Find a match</strong><span>Compare reports to spot items that could belong together.</span></div>
                                </li>
                                <li class="journey-step">
                                    <span class="journey-number">03</span>
                                    <div><strong>Verify ownership</strong><span>Review a claim before arranging a safe return.</span></div>
                                </li>
                                <li class="journey-step">
                                    <span class="journey-number">04</span>
                                    <div><strong>Return with confidence</strong><span>Record handover and keep a clear recovery history.</span></div>
                                </li>
                            </ol>
                            <div class="journey-note">One connected process, designed around trust and accountability.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="trust-strip" aria-label="Portal principles">
            <div class="container">
                <div class="row g-3">
                    <div class="col-md-4"><div class="trust-item"><span class="trust-symbol">01</span><span><strong>Easy to report</strong><small>Clear details help the community help.</small></span></div></div>
                    <div class="col-md-4"><div class="trust-item"><span class="trust-symbol">02</span><span><strong>Safer to recover</strong><small>Claims are checked before items are released.</small></span></div></div>
                    <div class="col-md-4"><div class="trust-item"><span class="trust-symbol">03</span><span><strong>Built for campus</strong><small>People and school assets, all in one place.</small></span></div></div>
                </div>
            </div>
        </section>

        <section class="features-section py-5 py-lg-6" id="features">
            <div class="container">
                <div class="section-heading text-center mx-auto mb-5">
                    <p class="eyebrow mb-2">WHAT THE PORTAL CAN DO</p>
                    <h2 class="display-6 fw-bold">More than a lost-and-found list</h2>
                    <p class="text-secondary mb-0">Each part supports a safer, more organized way to recover belongings and campus equipment.</p>
                </div>
                <div class="row g-3 g-lg-4">
                    <div class="col-md-6 col-lg-4">
                        <article class="feature-card h-100">
                            <span class="feature-number">01 / REPORT</span>
                            <h3>Lost &amp; found reporting</h3>
                            <p>Sign in and submit what went missing or what you picked up, with useful item details and a campus location.</p>
                            <span class="feature-status status-ready">AVAILABLE</span>
                        </article>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <article class="feature-card h-100">
                            <span class="feature-number">02 / DISCOVER</span>
                            <h3>Smart item matching</h3>
                            <p>Compare lost and found reports by name, color, description, place, and date, with a similarity score for possible matches.</p>
                            <span class="feature-status status-ready">AVAILABLE</span>
                        </article>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <article class="feature-card h-100">
                            <span class="feature-number">03 / PROTECT</span>
                            <h3>Ownership verification</h3>
                            <p>Let claimants provide identifying evidence and give administrators a chance to approve a claim before release.</p>
                            <span class="feature-status">PLANNED</span>
                        </article>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <article class="feature-card h-100">
                            <span class="feature-number">04 / TRACK</span>
                            <h3>Recovery history</h3>
                            <p>Follow an item's journey through reported, found, claimed, verified, and returned statuses with an activity record.</p>
                            <span class="feature-status status-ready">AVAILABLE</span>
                        </article>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <article class="feature-card h-100">
                            <span class="feature-number">05 / IMPROVE</span>
                            <h3>Campus insights</h3>
                            <p>Use location-based report statistics and charts to understand where items are most often lost.</p>
                            <span class="feature-status status-ready">AVAILABLE</span>
                        </article>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <article class="feature-card h-100">
                            <span class="feature-number">06 / ACCOUNT</span>
                            <h3>Asset QR &amp; handover codes</h3>
                            <p>Identify registered school assets with QR codes and confirm approved pickups with a one-time handover code.</p>
                            <span class="feature-status status-ready">AVAILABLE</span>
                        </article>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <article class="feature-card h-100">
                            <span class="feature-number">07 / ACCESS</span>
                            <h3>Email accounts &amp; admin view</h3>
                            <p>Users create accounts with email and password. Administrators sign in separately by role to review submitted reports and reporter details.</p>
                            <span class="feature-status status-ready">AVAILABLE</span>
                        </article>
                    </div>
                </div>
                <p class="feature-disclaimer text-center mt-4 mb-0">
                    Reports, recovery status updates, registered assets, and pickup confirmations are saved to the configured MySQL database. Ownership verification and other recovery tools are being developed in stages.
                </p>
            </div>
        </section>

        <section class="workflow-section py-5 py-lg-6" id="how-it-works">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-5">
                        <p class="eyebrow mb-2">SIMPLE FOR EVERYONE</p>
                        <h2 class="display-6 fw-bold mb-3">A better way to get things back.</h2>
                        <p class="text-secondary mb-4">Students, teachers, and campus staff can use one shared process to report an item and support a safe return.</p>
                        <a class="btn btn-primary px-4" href="report.php">Start a report <span aria-hidden="true">&rarr;</span></a>
                    </div>
                    <div class="col-lg-7">
                        <div class="workflow-panel">
                            <div class="workflow-row"><span>01</span><div><strong>Tell us what happened</strong><small>Choose lost or found, then add useful item details.</small></div></div>
                            <div class="workflow-row"><span>02</span><div><strong>Review possible matches</strong><small>Matching helps narrow down reports that may describe the same item.</small></div></div>
                            <div class="workflow-row"><span>03</span><div><strong>Prove and approve a claim</strong><small>Ownership evidence and administrator review help protect found items.</small></div></div>
                            <div class="workflow-row"><span>04</span><div><strong>Confirm the handover</strong><small>Track the return so everyone knows the item made it home.</small></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer py-4">
        <div class="container d-flex flex-column flex-sm-row justify-content-between gap-2">
            <a class="navbar-brand fw-bold" href="index.php"><span class="brand-mark" aria-hidden="true">C</span><span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span></a>
            <span class="small text-secondary align-self-sm-center">A safer way to reunite people with their belongings.</span>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
