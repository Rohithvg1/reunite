<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reunite — Find your way back</title>
  <meta name="description" content="A little community care goes a long way. Find lost items, report a find, and bring belongings home.">
  <link rel="stylesheet" href="assets/style.css">
  <script src="assets/app.js" defer></script>
</head>
<body>
<aside class="sidebar">
  <a class="brand" href="#browse" aria-label="Reunite home"><span class="brand-mark">r<span>↗</span></span>reunite<span class="brand-dot">.</span></a>
  <div class="workspace"><span class="workspace-icon">⌂</span><div><strong>Your community</strong><small>Lost & found, together</small></div><span class="live-dot"></span></div>
  <p class="nav-label">WORKSPACE</p>
  <nav aria-label="Main navigation">
    <a href="#browse" data-page="browse"><span data-icon="grid"></span>Browse items</a>
    <a href="#mine" data-page="mine"><span data-icon="folder"></span>My reports</a>
    <a href="#how" data-page="how"><span data-icon="heart"></span>How it works</a>
    <a href="#admin" data-page="admin" id="admin-link" hidden><span data-icon="shield"></span>Admin dashboard</a>
  </nav>
  <div class="sidebar-note"><span data-icon="heart"></span><strong>Good things find<br>their way back.</strong><p>Your small act of kindness could make someone’s day.</p><button class="text-button" data-report="found">Report a found item <span>↗</span></button></div>
  <div class="sidebar-bottom"><span class="mini-logo">r</span><div>Made for your community<small>A little care. A big difference.</small></div></div>
</aside>
<div class="shell">
  <header class="topbar"><div class="breadcrumb">Workspace <span>/</span> <strong id="crumb">Browse items</strong></div><div class="account" id="account"><button class="btn btn-outline btn-small" data-auth="login">Sign in</button><button class="btn btn-dark btn-small" data-auth="register">Join community <span>↗</span></button></div></header>
  <main id="main" tabindex="-1">
    <section id="browse-page">
      <div class="page-heading"><div><div class="eyebrow">A PLACE FOR SECOND CHANCES</div><h1>The community board<span>.</span></h1><p>Lost, found, and on their way home.</p></div><button class="btn btn-dark" data-report="lost"><span data-icon="plus"></span>Report an item</button></div>
      <section class="hero"><div class="hero-copy"><span class="hero-tag"><span class="live-dot"></span> Small acts. Happy reunions.</span><h2>Lost something?<br>Let’s bring it <em>home.</em></h2><p>A familiar place for the things you miss.<br>Browse the board or let your community help.</p><button class="btn btn-dark" data-report="found">I found something <span>↗</span></button></div><div class="hero-art" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="art-spark spark-one">✳</div><div class="art-spark spark-two">✦</div><div class="floating-note note-one">A little kindness <span>♡</span></div><div class="key-tag"><div class="tag-hole"></div><span class="tag-heart">♡</span><strong>back where<br>I belong.</strong><span class="tag-brand">reunite.</span></div><svg class="key-art" viewBox="0 0 160 250"><g transform="rotate(-24 80 125)"><circle cx="80" cy="65" r="41" fill="#efc568" stroke="#242e2a" stroke-width="3"/><circle cx="80" cy="65" r="22" fill="#d8eee0" stroke="#242e2a" stroke-width="3"/><path d="M68 105h24v108H68v-24H47v-20h21v-16H52v-20h16z" fill="#efc568" stroke="#242e2a" stroke-width="3" stroke-linejoin="round"/></g></svg><div class="floating-note note-two"><span class="check-circle">✓</span> Another happy reunion</div></div></section>
      <div class="stats" id="stats"><div><span class="stat-icon coral" data-icon="search"></span><div><strong id="stat-lost">—</strong><span>Looking for home</span></div><span class="stat-caption">Lost items</span></div><div><span class="stat-icon mint" data-icon="pin"></span><div><strong id="stat-found">—</strong><span>Waiting to be claimed</span></div><span class="stat-caption">Found items</span></div><div><span class="stat-icon lavender" data-icon="heart"></span><div><strong id="stat-returned">—</strong><span>Back where they belong</span></div><span class="stat-caption">Reunited</span></div></div>
      <section class="board" aria-labelledby="board-title"><div class="board-heading"><div><h2 id="board-title">Around the community <span id="item-count" class="count">0</span></h2><p>Someone’s everyday essential. Someone else’s good deed.</p></div><span class="sort-label"><span data-icon="clock"></span> Latest first</span></div><div class="filters"><div class="tabs" role="group" aria-label="Item status"><button class="active" data-filter="all">All items</button><button data-filter="lost">Lost</button><button data-filter="found">Found</button><button data-filter="returned">Reunited</button></div><div class="search-controls"><div class="search-field"><span data-icon="search"></span><input id="search" type="search" placeholder="Search items or places…" aria-label="Search items or places"></div><select id="category" aria-label="Filter category"><option value="">All categories</option></select></div></div><div id="items-grid" class="items-grid" aria-live="polite"><div class="empty"><div class="spinner"></div><h3>Getting the board ready…</h3></div></div><p class="demo-caption">Sample posts are labelled “Demo”. Add a report to start your own community board.</p></section>
    </section>
    <section id="mine-page" hidden></section><section id="how-page" hidden></section><section id="admin-page" hidden></section>
    <footer><span class="footer-brand">reunite.</span><span>Things get lost. Community brings them back.</span><a href="#how">A kinder way to find ↗</a></footer>
  </main>
</div>
<dialog id="modal"><div id="modal-content"></div></dialog><div id="toast" role="status" aria-live="polite"></div>
</body></html>
