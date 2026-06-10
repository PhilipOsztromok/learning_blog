"""
Generate: 10 Proven Methods for Making Money with AI (PDF)
Uses Chrome headless to render styled HTML to PDF.
"""

import subprocess, sys, os, tempfile

BASE       = r"C:\Users\emuba\OneDrive\My Learning\WebSites\website\claude-generated-files"
OUTPUT_PDF = os.path.join(BASE, "making_money_with_ai.pdf")

# ── Full HTML document ────────────────────────────────────────
html = """<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>10 Proven Methods for Making Money with AI</title>
<style>
  @page {
    size: A4;
    margin: 18mm 16mm 18mm 16mm;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Georgia', 'Times New Roman', serif;
    font-size: 10pt;
    color: #1a1a2e;
    background: #ffffff;
    line-height: 1.65;
  }

  /* ── Cover page ── */
  .cover {
    min-height: 267mm;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    page-break-after: always;
    background: linear-gradient(160deg, #0f0c29, #302b63, #24243e);
    color: #ffffff;
    padding: 20mm 15mm;
    border-radius: 0;
  }
  .cover-eyebrow {
    font-family: 'Arial', sans-serif;
    font-size: 9pt;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #a0b4ff;
    margin-bottom: 1.2em;
  }
  .cover h1 {
    font-size: 2.6rem;
    font-weight: bold;
    line-height: 1.2;
    color: #ffffff;
    margin-bottom: 0.5em;
    letter-spacing: -0.01em;
  }
  .cover h1 span { color: #79b8ff; }
  .cover .tagline {
    font-size: 1.05rem;
    color: #c8d8ff;
    font-style: italic;
    margin-bottom: 3em;
    max-width: 420px;
  }
  .cover-badges {
    display: flex;
    gap: 1em;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 3em;
  }
  .cover-badge {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 20px;
    padding: 0.35em 1em;
    font-family: 'Arial', sans-serif;
    font-size: 8.5pt;
    color: #ddeeff;
    letter-spacing: 0.04em;
  }
  .cover-toc {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    padding: 1.4em 2em;
    text-align: left;
    max-width: 480px;
    width: 100%;
  }
  .cover-toc h3 {
    font-family: 'Arial', sans-serif;
    font-size: 8pt;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #79b8ff;
    margin-bottom: 0.8em;
  }
  .cover-toc-item {
    display: flex;
    align-items: baseline;
    gap: 0.6em;
    padding: 0.22em 0;
    font-size: 9pt;
    color: #c8d8ff;
    font-family: 'Arial', sans-serif;
    border-bottom: 1px solid rgba(255,255,255,0.07);
  }
  .cover-toc-item:last-child { border-bottom: none; }
  .cover-toc-num {
    color: #79b8ff;
    font-weight: bold;
    min-width: 1.4em;
    font-size: 8.5pt;
  }

  /* ── General typography ── */
  h2 {
    font-family: 'Arial', sans-serif;
    font-size: 1.4rem;
    color: #1a1a2e;
    margin: 0 0 0.5em 0;
  }
  h3 {
    font-family: 'Arial', sans-serif;
    font-size: 1rem;
    color: #302b63;
    margin: 1em 0 0.3em 0;
  }
  p { margin-bottom: 0.7em; }
  ul { padding-left: 1.4em; margin-bottom: 0.7em; }
  li { margin-bottom: 0.25em; }
  strong { color: #1a1a2e; }

  /* ── Method cards ── */
  .method {
    page-break-inside: avoid;
    margin-bottom: 2em;
    border: 1px solid #e0e4f0;
    border-radius: 8px;
    overflow: hidden;
  }
  .method-header {
    display: flex;
    align-items: center;
    gap: 1em;
    background: linear-gradient(90deg, #302b63, #24243e);
    padding: 0.9em 1.2em;
    color: #ffffff;
  }
  .method-num {
    font-family: 'Arial', sans-serif;
    font-size: 1.5rem;
    font-weight: bold;
    color: #79b8ff;
    min-width: 2em;
    text-align: center;
    line-height: 1;
  }
  .method-title {
    font-family: 'Arial', sans-serif;
    font-size: 1.05rem;
    font-weight: bold;
    color: #ffffff;
    flex: 1;
  }
  .method-emoji {
    font-size: 1.4rem;
  }
  .method-body {
    padding: 1em 1.2em 0.8em 1.2em;
    background: #fafbff;
  }
  .method-lead {
    font-size: 10.5pt;
    color: #2a2a4a;
    font-style: italic;
    margin-bottom: 0.7em;
    border-left: 3px solid #79b8ff;
    padding-left: 0.8em;
  }

  /* ── Stat pills ── */
  .stat-row {
    display: flex;
    gap: 0.7em;
    flex-wrap: wrap;
    margin: 0.8em 0;
  }
  .stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #ffffff;
    border: 1px solid #d0d8f0;
    border-radius: 6px;
    padding: 0.45em 0.9em;
    min-width: 90px;
  }
  .stat-label {
    font-family: 'Arial', sans-serif;
    font-size: 7pt;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7a8aaa;
    margin-bottom: 0.15em;
  }
  .stat-value {
    font-family: 'Arial', sans-serif;
    font-size: 9.5pt;
    font-weight: bold;
    color: #302b63;
  }
  .stat-value.green  { color: #1a6e3a; }
  .stat-value.amber  { color: #7a4800; }
  .stat-value.blue   { color: #1a3a7a; }

  /* ── Info grid (Tools / Platforms) ── */
  .info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.6em;
    margin-top: 0.8em;
  }
  .info-box {
    background: #ffffff;
    border: 1px solid #d8ddf0;
    border-radius: 5px;
    padding: 0.5em 0.8em;
  }
  .info-box-title {
    font-family: 'Arial', sans-serif;
    font-size: 7.5pt;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #302b63;
    font-weight: bold;
    margin-bottom: 0.3em;
  }
  .info-box ul {
    margin: 0; padding-left: 1.1em;
    font-size: 8.5pt;
    color: #3a3a5a;
  }
  .info-box li { margin-bottom: 0.1em; }

  /* ── Watch-out callout ── */
  .watchout {
    background: #fffbe6;
    border-left: 3px solid #f0a040;
    padding: 0.5em 0.8em;
    margin-top: 0.8em;
    border-radius: 0 4px 4px 0;
    font-size: 9pt;
    color: #5a4000;
  }
  .watchout strong { color: #7a4800; }

  .tip-box {
    background: #f0fff4;
    border-left: 3px solid #3fb950;
    padding: 0.5em 0.8em;
    margin-top: 0.6em;
    border-radius: 0 4px 4px 0;
    font-size: 9pt;
    color: #1a3a1a;
  }
  .tip-box strong { color: #1a6e3a; }

  /* ── Summary table ── */
  .summary-page {
    page-break-before: always;
  }
  .summary-page h2 {
    text-align: center;
    margin-bottom: 0.3em;
  }
  .summary-page .intro {
    text-align: center;
    color: #7a8aaa;
    font-style: italic;
    font-size: 9.5pt;
    margin-bottom: 1.5em;
  }
  table.summary {
    width: 100%;
    border-collapse: collapse;
    font-size: 8.5pt;
    font-family: 'Arial', sans-serif;
  }
  table.summary th {
    background: #302b63;
    color: #ffffff;
    padding: 0.5em 0.7em;
    text-align: left;
    font-weight: bold;
    letter-spacing: 0.04em;
  }
  table.summary td {
    padding: 0.45em 0.7em;
    border-bottom: 1px solid #e4e8f4;
    vertical-align: top;
  }
  table.summary tr:nth-child(even) td { background: #f5f6ff; }
  .dot-green  { color: #1a6e3a; font-weight: bold; font-size: 10pt; }
  .dot-amber  { color: #7a4800; font-weight: bold; font-size: 10pt; }
  .dot-red    { color: #7a1a1a; font-weight: bold; font-size: 10pt; }

  .footer-note {
    margin-top: 2em;
    text-align: center;
    font-size: 8pt;
    color: #9aabcc;
    font-style: italic;
    font-family: 'Arial', sans-serif;
    border-top: 1px solid #e0e4f0;
    padding-top: 1em;
  }
</style>
</head>
<body>

<!-- ══════════════════════════════════════════════ COVER ══ -->
<div class="cover">
  <div class="cover-eyebrow">Practical AI Income Guide &bull; 2025</div>
  <h1>10 Proven Methods<br>for Making Money<br>with <span>AI</span></h1>
  <p class="tagline">A beginner-friendly overview — no expertise required, little to no upfront investment</p>
  <div class="cover-badges">
    <span class="cover-badge">Zero to Low Cost</span>
    <span class="cover-badge">Beginner Friendly</span>
    <span class="cover-badge">Start This Week</span>
    <span class="cover-badge">Real Income Potential</span>
  </div>
  <div class="cover-toc">
    <h3>Contents</h3>
    <div class="cover-toc-item"><span class="cover-toc-num">01</span> AI-Generated Books on Amazon KDP</div>
    <div class="cover-toc-item"><span class="cover-toc-num">02</span> Freelance AI Content Writing</div>
    <div class="cover-toc-item"><span class="cover-toc-num">03</span> Selling AI Art on Print-on-Demand</div>
    <div class="cover-toc-item"><span class="cover-toc-num">04</span> YouTube Automation Channels</div>
    <div class="cover-toc-item"><span class="cover-toc-num">05</span> AI Chatbots for Small Businesses</div>
    <div class="cover-toc-item"><span class="cover-toc-num">06</span> Selling Prompts on Prompt Marketplaces</div>
    <div class="cover-toc-item"><span class="cover-toc-num">07</span> Social Media Management with AI</div>
    <div class="cover-toc-item"><span class="cover-toc-num">08</span> AI Voiceover &amp; Audiobook Production</div>
    <div class="cover-toc-item"><span class="cover-toc-num">09</span> Online Course Creation with AI</div>
    <div class="cover-toc-item"><span class="cover-toc-num">10</span> AI-Powered Affiliate Marketing</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ INTRO ══ -->
<p style="font-size:10.5pt; color:#2a2a4a; margin-bottom:1.4em;">
The AI revolution has created a wave of genuine income opportunities that require no technical background and very little money to start.
This guide gives you a plain-English overview of ten of the most accessible routes — what each one involves, what tools you need, and a realistic sense of the earning potential.
None of these require a degree, coding skills, or significant capital.
</p>

<!-- ══════════════════════════════════════════════ METHOD 1 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">01</span>
    <span class="method-title">AI-Generated Books on Amazon KDP</span>
    <span class="method-emoji">📚</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Use AI to write and publish ebooks or low-content books (journals, planners, activity books) on Amazon Kindle Direct Publishing — then earn royalties every time someone buys.</p>
    <p>Amazon KDP is completely free to use. You upload a manuscript, set your price, and Amazon handles sales, payments, and delivery. AI tools like ChatGPT can draft an entire short ebook in minutes. Popular niches include how-to guides, colouring books (with AI-generated artwork), puzzle books, niche journals, and short non-fiction.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Very Low</span></div>
      <div class="stat"><span class="stat-label">Time to first sale</span><span class="stat-value blue">1–4 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$100–$5k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">Key AI Tools</div>
        <ul><li>ChatGPT / Claude (writing)</li><li>Midjourney / DALL-E (covers)</li><li>Canva (formatting &amp; layout)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Best Book Types to Start</div>
        <ul><li>Niche journals &amp; planners</li><li>Short how-to guides (5,000 words)</li><li>Low-content activity books</li></ul>
      </div>
    </div>
    <div class="tip-box"><strong>Quick start tip:</strong> Low-content books (journals, habit trackers, notebooks) are the fastest to produce. A well-designed journal can be created in an afternoon using Canva and requires no writing at all.</div>
    <div class="watchout"><strong>Watch out:</strong> Quality matters more than quantity. Amazon has flagged and removed poorly-reviewed AI books. Focus on a specific niche and make sure content is genuinely useful.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 2 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">02</span>
    <span class="method-title">Freelance AI Content Writing</span>
    <span class="method-emoji">&#9997;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Offer blog posts, product descriptions, email newsletters, and website copy to businesses — using AI to work faster and take on more clients than a traditional writer could.</p>
    <p>Businesses of all sizes need a constant supply of written content. Many are happy to pay a human to manage and edit AI-generated drafts, because they want someone accountable for quality. You do not write everything from scratch — you prompt, curate, edit, and deliver polished content. The AI does the heavy lifting; you provide direction and quality control.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free–$20/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Low</span></div>
      <div class="stat"><span class="stat-label">Time to first client</span><span class="stat-value blue">1–2 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$500–$8k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">Key AI Tools</div>
        <ul><li>ChatGPT / Claude (drafting)</li><li>Grammarly (editing)</li><li>SurferSEO (SEO optimising)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Where to Find Clients</div>
        <ul><li>Upwork &amp; Fiverr (beginner-friendly)</li><li>LinkedIn outreach</li><li>Local business Facebook groups</li></ul>
      </div>
    </div>
    <div class="tip-box"><strong>Quick start tip:</strong> Create a free Fiverr or Upwork profile today. Offer one specific service (e.g. "5 SEO blog posts for your business") rather than being generic. A clear niche attracts better clients.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 3 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">03</span>
    <span class="method-title">Selling AI Art on Print-on-Demand</span>
    <span class="method-emoji">&#127912;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Generate artwork with AI image tools and sell it on products — t-shirts, mugs, phone cases, wall prints — through platforms that print and ship everything for you.</p>
    <p>Print-on-demand (POD) platforms like Redbubble, Merch by Amazon, and Printful let you upload designs and sell them on physical products with zero inventory and zero upfront cost. When a customer orders a mug with your design on it, the platform prints and ships it — you receive a royalty. AI image generators (Midjourney, DALL-E, Adobe Firefly) can produce print-ready artwork in seconds.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free–$30/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Very Low</span></div>
      <div class="stat"><span class="stat-label">Time to first sale</span><span class="stat-value blue">1–3 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$100–$3k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">AI Image Tools</div>
        <ul><li>Midjourney (best quality)</li><li>DALL-E 3 via ChatGPT</li><li>Adobe Firefly (commercially safe)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Best POD Platforms</div>
        <ul><li>Redbubble (easiest to start)</li><li>Merch by Amazon (best reach)</li><li>Etsy + Printful (most control)</li></ul>
      </div>
    </div>
    <div class="tip-box"><strong>Quick start tip:</strong> Niche designs outsell generic ones massively. Think "corgi yoga poses" not "cute dogs". Pick a hobby or interest community and flood it with relevant designs.</div>
    <div class="watchout"><strong>Watch out:</strong> Check the commercial use terms of any AI image tool before selling. Adobe Firefly and DALL-E 3 explicitly allow commercial use. Midjourney requires a paid plan for commercial rights.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 4 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">04</span>
    <span class="method-title">YouTube Automation Channels</span>
    <span class="method-emoji">&#127909;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Build a YouTube channel where AI handles the script, voiceover, and visuals — you never appear on camera. Monetise through YouTube ads, sponsorships, and affiliate links.</p>
    <p>Faceless YouTube channels covering topics like finance tips, history, motivational content, or "top 10" lists are a proven format. AI writes the script, a text-to-speech tool records the voiceover, and free video editors or AI video tools assemble the visuals. It takes a few months to build an audience, but once established it generates largely passive income.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free–$50/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value amber">Low–Medium</span></div>
      <div class="stat"><span class="stat-label">Time to monetise</span><span class="stat-value amber">3–9 months</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$500–$10k+/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">AI Tools Used</div>
        <ul><li>ChatGPT (scripts)</li><li>ElevenLabs (voiceover)</li><li>Pictory / InVideo (video editing)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Popular Niches</div>
        <ul><li>Personal finance &amp; investing</li><li>History &amp; mystery</li><li>Motivational / self-improvement</li></ul>
      </div>
    </div>
    <div class="watchout"><strong>Watch out:</strong> YouTube requires 1,000 subscribers and 4,000 watch hours before monetisation. This takes time. Treat it as a medium-term project rather than a quick income source.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 5 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">05</span>
    <span class="method-title">AI Chatbots for Small Businesses</span>
    <span class="method-emoji">&#129302;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Build and sell simple AI chatbots to local businesses — restaurants, salons, estate agents — that answer customer questions automatically. No coding required.</p>
    <p>Thousands of small businesses would benefit from a chatbot on their website to handle FAQs, take bookings, or qualify leads — but they have no idea how to build one. No-code platforms like ManyChat, Tidio, and Chatbase let you create and deploy a fully functional chatbot in hours. You can charge a setup fee plus a monthly retainer to maintain it.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free–$50/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Low</span></div>
      <div class="stat"><span class="stat-label">Time to first client</span><span class="stat-value blue">1–3 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$1k–$5k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">No-Code Chatbot Tools</div>
        <ul><li>Chatbase (easiest, GPT-powered)</li><li>ManyChat (social media focus)</li><li>Tidio (website chat + email)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Best Target Businesses</div>
        <ul><li>Restaurants &amp; takeaways</li><li>Hair &amp; beauty salons</li><li>Estate &amp; letting agents</li></ul>
      </div>
    </div>
    <div class="tip-box"><strong>Quick start tip:</strong> Pick one type of business (e.g. hair salons), build a demo chatbot for a fictional salon, and use it to cold-approach 20 real salons. A live demo is worth a thousand words.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 6 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">06</span>
    <span class="method-title">Selling Prompts on Prompt Marketplaces</span>
    <span class="method-emoji">&#128161;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Write and sell high-quality prompts for AI tools (ChatGPT, Midjourney, Stable Diffusion) on dedicated marketplaces. A well-crafted prompt can sell hundreds of times.</p>
    <p>As AI tools have proliferated, so has demand for prompts that reliably produce great results. Most people struggle to get the output they want from tools like Midjourney or ChatGPT. If you can write a prompt that consistently generates stunning portrait photography, or that produces a complete business plan in minutes, other users will pay for it. Prices range from $1.99 to $20+ per prompt.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Low</span></div>
      <div class="stat"><span class="stat-label">Time to first sale</span><span class="stat-value blue">Days–2 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$100–$2k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">Where to Sell</div>
        <ul><li>PromptBase (largest marketplace)</li><li>Etsy (growing prompt category)</li><li>Gumroad (self-hosted)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Best-Selling Prompt Types</div>
        <ul><li>Midjourney art &amp; photography styles</li><li>ChatGPT business &amp; marketing</li><li>Stable Diffusion character design</li></ul>
      </div>
    </div>
    <div class="tip-box"><strong>Quick start tip:</strong> Browse PromptBase's bestseller list to see what sells. Find a category with demand but fewer listings and focus there. Include a sample output image with every listing.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 7 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">07</span>
    <span class="method-title">Social Media Management with AI</span>
    <span class="method-emoji">&#128241;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Manage social media accounts for small businesses using AI to generate posts, captions, and graphics at scale — doing in 2 hours what used to take a full work week.</p>
    <p>Small business owners are desperate for consistent social media presence but lack the time to maintain it. AI can generate a month's worth of content ideas and captions in minutes. You act as the account manager: using AI to produce the content, scheduling it with tools like Buffer or Later, and reporting on results. This is one of the easiest services to package and sell repeatedly.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free–$30/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Low</span></div>
      <div class="stat"><span class="stat-label">Time to first client</span><span class="stat-value blue">1–2 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$1k–$6k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">AI Tools</div>
        <ul><li>ChatGPT / Claude (captions &amp; copy)</li><li>Canva AI (graphics)</li><li>Buffer / Later (scheduling)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Typical Packages</div>
        <ul><li>Starter: 12 posts/mo — ~$300</li><li>Growth: 20 posts/mo — ~$600</li><li>Full: daily posting — ~$1,200</li></ul>
      </div>
    </div>
    <div class="tip-box"><strong>Quick start tip:</strong> Start with one platform (Instagram or Facebook). Offer a free trial week to your first client to get a testimonial, then use that to land paying clients.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 8 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">08</span>
    <span class="method-title">AI Voiceover &amp; Audiobook Production</span>
    <span class="method-emoji">&#127908;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Use AI voice cloning and text-to-speech tools to produce professional-quality audio — sell voiceover services to clients or convert public-domain books into audiobooks for sale.</p>
    <p>AI voice tools like ElevenLabs now produce speech indistinguishable from a human narrator. You can offer voiceover production to YouTubers, podcasters, and e-learning creators, or take classic books whose copyright has expired (pre-1927 works) and produce audiobooks to sell on ACX (Audible's platform) or Findaway Voices.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free–$22/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Very Low</span></div>
      <div class="stat"><span class="stat-label">Time to first income</span><span class="stat-value blue">1–3 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$200–$3k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">Key Tools</div>
        <ul><li>ElevenLabs (best AI voices)</li><li>Murf.ai (professional quality)</li><li>Audacity (free audio editing)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Where to Sell</div>
        <ul><li>ACX / Audible (audiobooks)</li><li>Fiverr (voiceover services)</li><li>Voices.com (larger jobs)</li></ul>
      </div>
    </div>
    <div class="watchout"><strong>Watch out:</strong> Always disclose AI voice use when selling voiceover services — misrepresenting AI audio as human-recorded breaches most platform terms. Many clients are happy with AI voice when it is priced accordingly.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 9 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">09</span>
    <span class="method-title">Online Course Creation with AI</span>
    <span class="method-emoji">&#127891;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Use AI to plan, script, and produce online courses on topics you already know something about — then sell them on Udemy, Teachable, or Gumroad for passive income.</p>
    <p>You do not need to be a world expert — you need to know more than a complete beginner. AI can structure a curriculum, write lesson scripts, generate slides, and even create quiz questions. Many successful Udemy instructors produce courses in a weekend using AI assistance. Udemy has over 60 million students actively searching for courses.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value green">Free–$30/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value amber">Low–Medium</span></div>
      <div class="stat"><span class="stat-label">Time to launch</span><span class="stat-value amber">2–6 weeks</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$200–$5k/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">AI Tools</div>
        <ul><li>ChatGPT (curriculum &amp; scripts)</li><li>Canva / Gamma (slide decks)</li><li>Descript (recording &amp; editing)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Best Platforms</div>
        <ul><li>Udemy (built-in audience)</li><li>Teachable / Thinkific (own brand)</li><li>Gumroad (simplest setup)</li></ul>
      </div>
    </div>
    <div class="tip-box"><strong>Quick start tip:</strong> Before creating the course, post the title on Reddit or in Facebook groups and ask if people would buy it. Free validation before you spend time on production.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ METHOD 10 ══ -->
<div class="method">
  <div class="method-header">
    <span class="method-num">10</span>
    <span class="method-title">AI-Powered Affiliate Marketing</span>
    <span class="method-emoji">&#128279;</span>
  </div>
  <div class="method-body">
    <p class="method-lead">Build a niche website or newsletter using AI-generated content, drive traffic to it, and earn commissions when readers click your affiliate links and make purchases.</p>
    <p>Affiliate marketing pays you a commission (typically 5–50%) when someone buys a product through your unique link. AI can produce an entire product review site — complete with articles, comparisons, and buying guides — in days rather than months. Popular platforms include Amazon Associates (easy entry), and niche-specific programmes from software companies which pay 20–40% recurring commissions.</p>
    <div class="stat-row">
      <div class="stat"><span class="stat-label">Start-up cost</span><span class="stat-value amber">$10–$50/mo</span></div>
      <div class="stat"><span class="stat-label">Expertise needed</span><span class="stat-value green">Low</span></div>
      <div class="stat"><span class="stat-label">Time to first income</span><span class="stat-value amber">2–6 months</span></div>
      <div class="stat"><span class="stat-label">Income potential</span><span class="stat-value blue">$500–$15k+/mo</span></div>
    </div>
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-title">AI Tools</div>
        <ul><li>ChatGPT / Claude (articles)</li><li>WordPress + RankMath (SEO)</li><li>Canva (graphics &amp; images)</li></ul>
      </div>
      <div class="info-box">
        <div class="info-box-title">Best Affiliate Programmes</div>
        <ul><li>Amazon Associates (any niche)</li><li>ShareASale / CJ (thousands of brands)</li><li>SaaS tools (highest commissions)</li></ul>
      </div>
    </div>
    <div class="watchout"><strong>Watch out:</strong> AI-generated content must be edited and fact-checked before publishing. Google's algorithms are increasingly good at identifying thin, low-value AI content. Quality and genuine helpfulness are what rank.</div>
    <div class="tip-box"><strong>Quick start tip:</strong> Choose a very narrow niche ("best AI tools for dog trainers") rather than a broad one. Narrow niches have lower competition and are easier to rank for in search results.</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════ SUMMARY ══ -->
<div class="summary-page">
  <h2>At-a-Glance Comparison</h2>
  <p class="intro">A quick summary of all ten methods to help identify which might suit you best</p>

  <table class="summary">
    <tr>
      <th>#</th>
      <th>Method</th>
      <th>Start Cost</th>
      <th>Effort</th>
      <th>Time to Income</th>
      <th>Passive?</th>
      <th>Ceiling</th>
    </tr>
    <tr>
      <td>01</td><td>KDP Books</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>1–4 wks</td>
      <td><span class="dot-green">&#9679;</span> Yes</td>
      <td>$5k/mo</td>
    </tr>
    <tr>
      <td>02</td><td>Content Writing</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>1–2 wks</td>
      <td><span class="dot-amber">&#9679;</span> No</td>
      <td>$8k/mo</td>
    </tr>
    <tr>
      <td>03</td><td>AI Art / POD</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>1–3 wks</td>
      <td><span class="dot-green">&#9679;</span> Yes</td>
      <td>$3k/mo</td>
    </tr>
    <tr>
      <td>04</td><td>YouTube Automation</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-amber">&#9679;</span> Medium</td>
      <td>3–9 months</td>
      <td><span class="dot-green">&#9679;</span> Mostly</td>
      <td>$10k+/mo</td>
    </tr>
    <tr>
      <td>05</td><td>AI Chatbots</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>1–3 wks</td>
      <td><span class="dot-amber">&#9679;</span> Retainer</td>
      <td>$5k/mo</td>
    </tr>
    <tr>
      <td>06</td><td>Prompt Selling</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>Days</td>
      <td><span class="dot-green">&#9679;</span> Yes</td>
      <td>$2k/mo</td>
    </tr>
    <tr>
      <td>07</td><td>Social Media Mgmt</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>1–2 wks</td>
      <td><span class="dot-amber">&#9679;</span> No</td>
      <td>$6k/mo</td>
    </tr>
    <tr>
      <td>08</td><td>AI Voiceover</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>1–3 wks</td>
      <td><span class="dot-amber">&#9679;</span> Mixed</td>
      <td>$3k/mo</td>
    </tr>
    <tr>
      <td>09</td><td>Online Courses</td>
      <td><span class="dot-green">&#9679;</span> Free</td>
      <td><span class="dot-amber">&#9679;</span> Medium</td>
      <td>2–6 wks</td>
      <td><span class="dot-green">&#9679;</span> Mostly</td>
      <td>$5k/mo</td>
    </tr>
    <tr>
      <td>10</td><td>Affiliate Marketing</td>
      <td><span class="dot-amber">&#9679;</span> $10–50/mo</td>
      <td><span class="dot-green">&#9679;</span> Low</td>
      <td>2–6 months</td>
      <td><span class="dot-green">&#9679;</span> Yes</td>
      <td>$15k+/mo</td>
    </tr>
  </table>

  <p style="font-size:8pt; color:#9aabcc; text-align:center; margin-top:0.5em; font-family:Arial,sans-serif;">
    Income figures represent realistic ranges reported by practitioners — not guarantees. Results vary with effort, niche, and consistency.
  </p>

  <div style="margin-top:2em; background:#f5f6ff; border:1px solid #d0d8f0; border-radius:8px; padding:1.2em 1.4em;">
    <h3 style="margin-top:0; color:#302b63;">How to Choose Where to Start</h3>
    <p style="font-size:9.5pt;">
      <strong>Want income quickly?</strong> Start with freelance content writing (#2), social media management (#7), or prompt selling (#6) — these can generate your first income within days or weeks.<br><br>
      <strong>Want passive income?</strong> KDP books (#1), AI art on print-on-demand (#3), and affiliate marketing (#10) build assets that earn while you sleep — but require more patience upfront.<br><br>
      <strong>Want the highest ceiling?</strong> YouTube automation (#4) and affiliate marketing (#10) have the largest long-term income potential, though both take several months to get going.<br><br>
      <strong>Best all-rounder for beginners:</strong> Freelance content writing (#2) — fastest to start, no investment, skills transfer to everything else on this list.
    </p>
  </div>

  <div class="footer-note">
    This document is an overview for exploration purposes. All income figures are approximate ranges from public sources and community reports.<br>
    None of the methods described constitute financial advice. Always verify platform terms before selling.
  </div>
</div>

</body>
</html>
"""

# ── Write temp file ───────────────────────────────────────────
tmp = tempfile.NamedTemporaryFile(suffix=".html", delete=False, mode="w", encoding="utf-8")
tmp.write(html)
tmp.close()

# ── Find Chrome ───────────────────────────────────────────────
chrome_candidates = [
    r"C:\Program Files\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    r"C:\Users\emuba\AppData\Local\Google\Chrome\Application\chrome.exe",
]
chrome = next((c for c in chrome_candidates if os.path.exists(c)), None)
if not chrome:
    print("ERROR: Chrome not found.")
    sys.exit(1)

print(f"Chrome: {chrome}")
print(f"Output: {OUTPUT_PDF}")
print("Generating PDF...")

cmd = [
    chrome,
    "--headless=new",
    "--disable-gpu",
    "--no-sandbox",
    "--disable-software-rasterizer",
    f"--print-to-pdf={OUTPUT_PDF}",
    "--print-to-pdf-no-header",
    "--no-pdf-header-footer",
    f"file:///{tmp.name.replace(os.sep, '/')}",
]

result = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
os.unlink(tmp.name)

if result.returncode == 0 and os.path.exists(OUTPUT_PDF):
    size_kb = os.path.getsize(OUTPUT_PDF) // 1024
    print(f"Done! PDF size: {size_kb} KB")
    print(f"Saved to: {OUTPUT_PDF}")
else:
    print(f"Chrome exit code: {result.returncode}")
    if result.stderr:
        print(result.stderr[:800])
    sys.exit(1)
