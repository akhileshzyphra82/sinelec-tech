<?php
require_once '../data/store_data.php';
$currentPage = 'about';
$pageTitle   = 'About Sinelec - Sinelec Tech';
$teamProfiles = [
    [
        'name' => 'Aarav Mehta',
        'role' => 'Director, OEM Partnerships',
        'summary' => 'Leads strategic customer relationships, commercial planning, and high-priority OEM sourcing programs across industrial and embedded electronics.',
        'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=320&q=80',
    ],
    [
        'name' => 'Shruti Nair',
        'role' => 'Senior Embedded Solutions Lead',
        'summary' => 'Supports programming workflows, MCU selection, and production-aligned engineering coordination for electronics teams shipping real products.',
        'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=320&q=80',
    ],
    [
        'name' => 'Rohan Verma',
        'role' => 'Supply Chain Operations Head',
        'summary' => 'Owns sourcing visibility, allocation follow-ups, delivery planning, and responsive execution for urgent and repeat component requirements.',
        'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=320&q=80',
    ],
    [
        'name' => 'Neha Kapoor',
        'role' => 'Customer Success Manager',
        'summary' => 'Coordinates quote turnaround, account communication, and project continuity so customer teams stay informed from enquiry to delivery.',
        'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=320&q=80',
    ],
    [
        'name' => 'Vikram Iyer',
        'role' => 'Technical Program Specialist',
        'summary' => 'Bridges technical scope with commercial execution for OEM batches, firmware-readiness reviews, and implementation-side issue resolution.',
        'avatar' => 'https://images.unsplash.com/photo-1504593811423-6dd665756598?auto=format&fit=crop&w=320&q=80',
    ],
    [
        'name' => 'Pooja Sharma',
        'role' => 'Business Development Manager',
        'summary' => 'Drives new client engagement, segment expansion, and consultative support for startups and established electronics businesses.',
        'avatar' => 'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=320&q=80',
    ],
];
$newsItems = [
    [
        'date' => 'March 18, 2026',
        'title' => 'Expanded support for MCU and wireless module sourcing',
        'description' => 'We strengthened supply support for fast-moving microcontrollers, development modules, and connected electronics programs for OEM and R&D teams.',
        'image' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'date' => 'January 09, 2026',
        'title' => 'Dedicated chip programming workflow for OEM batches',
        'description' => 'A structured programming and validation flow now supports repeatable batch orders that require firmware loading, verification, and dispatch readiness.',
        'image' => 'https://images.unsplash.com/photo-1581092335397-9583eb92d232?auto=format&fit=crop&w=900&q=80',
    ],
    [
        'date' => 'November 22, 2025',
        'title' => 'New customer engagement process for quote turnaround',
        'description' => 'We streamlined inbound quote handling to improve response speed for urgent procurement, BOM-based sourcing, and design-linked buying requests.',
        'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=900&q=80',
    ],
];
require_once 'header.php';
?>

<main>
<section class="about-corp-hero">
  <div class="wrap about-corp-hero-grid">
    <div class="about-corp-hero-copy">
      <h1 class="about-corp-title">Powering Electronics From Concept to Production</h1>
      <p class="about-corp-sub">We help OEMs, product teams, and industrial buyers move from concept to production with dependable electronics sourcing, engineering support, and responsive commercial execution.</p>
      <div class="about-corp-actions">
        <a href="#contact" class="about-corp-primary-btn">
          <span class="about-corp-primary-btn-text">
            <strong>Contact Us</strong>
            <small>Talk to our electronics team</small>
          </span>
          <span class="about-corp-primary-btn-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.81 19.79 19.79 0 01.1 2.27 2 2 0 012.07.1h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.16 6.16l.91-.91a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
          </span>
        </a>
        <a href="request-a-quote" class="about-corp-link">
          <span class="about-corp-link-copy">
            <strong>Request a Quote</strong>
            <small>Fast response for OEM and bulk enquiries</small>
          </span>
          <span class="about-corp-link-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </span>
        </a>
      </div>
      <div class="about-corp-mini-metrics">
        <div class="about-corp-mini-card">
          <span class="about-corp-mini-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <strong class="about-corp-mini-value">12+</strong>
          <span class="about-corp-mini-label">Happy customers</span>
        </div>
        <div class="about-corp-mini-card">
          <span class="about-corp-mini-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/><path d="M3 6h18"/><path d="M3 18h12"/></svg>
          </span>
          <strong class="about-corp-mini-value">23+</strong>
          <span class="about-corp-mini-label">Projects delivered</span>
        </div>
        <div class="about-corp-mini-card">
          <span class="about-corp-mini-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </span>
          <strong class="about-corp-mini-value">2</strong>
          <span class="about-corp-mini-label">Locations</span>
        </div>
      </div>
    </div>
    <div class="about-corp-hero-visual">
      <div class="about-corp-image-stack">
        <img src="https://plus.unsplash.com/premium_photo-1683120972279-87efe2ba252f?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OXx8c2VtaWNvbmR1Y3RvcnxlbnwwfHwwfHx8MA%3D%3D" alt="Semiconductor components and circuit board close-up" class="about-corp-image about-corp-image-main">
      </div>
    </div>
  </div>
</section>

<div class="wrap page-wrap about-corp-page">

  <section class="about-corp-section" id="about-us">
    <div class="about-corp-section-head">
      <div>
        <div class="sec-title">About Us</div>
        <div class="sec-subtitle">Built for serious electronics buyers and product teams.</div>
      </div>
    </div>
    <div class="about-corp-story-grid">
      <div class="about-corp-story-copy">
        <p>Sinelec is an electronics-focused sourcing and design support company serving OEMs, startups, R&amp;D teams, and industrial buyers. We combine technical understanding with supply execution, helping customers reduce sourcing friction and move faster from prototype to production.</p>
        <p>Our work spans semiconductor distribution, chip programming, component sourcing, embedded support, and manufacturing coordination. That means clients do not just get access to parts, they get a partner who understands design constraints, timelines, quality expectations, and commercial realities.</p>
        <p>With a modern service mindset and a practical engineering foundation, we aim to be the dependable bridge between idea, design validation, and final deliverable.</p>
      </div>
      <div class="about-corp-story-card">
        <div class="about-corp-story-card-title">Why companies work with Sinelec</div>
        <div class="about-corp-check-list">
          <div><span class="about-corp-check-icon">&#10003;</span><span>Responsive sourcing for semiconductors and electronic modules</span></div>
          <div><span class="about-corp-check-icon">&#10003;</span><span>Engineering-aligned communication for product and purchasing teams</span></div>
          <div><span class="about-corp-check-icon">&#10003;</span><span>Support from design concept to supply execution</span></div>
          <div><span class="about-corp-check-icon">&#10003;</span><span>Flexible engagement for prototype, small-batch, and OEM scale</span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="about-corp-section" id="our-segment">
    <div class="about-corp-section-head">
      <div>
        <div class="sec-title">Our Segment</div>
        <div class="sec-subtitle">Focused capabilities across electronics sourcing and design support.</div>
      </div>
    </div>
    <div class="about-corp-segment-grid">
      <article class="about-corp-segment-card">
        <div class="about-corp-segment-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
        </div>
        <h3>Semiconductor Distribution</h3>
        <p>Supply of genuine ICs, microcontrollers, sensors, communication devices, and supporting components for development and production teams.</p>
      </article>
      <article class="about-corp-segment-card">
        <div class="about-corp-segment-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M17 7l5 5-5 5M7 7l-5 5 5 5M14 3l-4 18"/></svg>
        </div>
        <h3>Programming &amp; Embedded Support</h3>
        <p>Firmware flashing, device configuration, chip-level programming, and practical support for embedded product deployment.</p>
      </article>
      <article class="about-corp-segment-card">
        <div class="about-corp-segment-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 7h18"/><path d="M6 3v8"/><path d="M18 3v8"/><rect x="3" y="11" width="18" height="10" rx="2"/></svg>
        </div>
        <h3>OEM Project Support</h3>
        <p>Commercial and technical coordination for OEM customers who need structured execution from concept stage through finished electronics output.</p>
      </article>
      <article class="about-corp-segment-card">
        <div class="about-corp-segment-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
        </div>
        <h3>Component Sourcing &amp; Logistics</h3>
        <p>Procurement assistance, availability matching, and dependable coordination for priority orders, design cycles, and delivery deadlines.</p>
      </article>
    </div>
  </section>

  <section class="about-corp-section" id="teams">
    <div class="about-team-topbar">
      <div class="about-corp-section-head">
        <div>
          <div class="sec-title">Teams</div>
          <div class="sec-subtitle">Cross-functional people supporting sourcing, delivery, and technical execution.</div>
        </div>
      </div>
      <div class="about-team-nav" aria-label="Team profile navigation">
        <button type="button" class="about-team-nav-btn" id="aboutTeamPrev" aria-label="Previous team profiles">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button type="button" class="about-team-nav-btn" id="aboutTeamNext" aria-label="Next team profiles">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    <div class="about-team-slider">
      <div class="about-team-track" id="aboutTeamTrack">
        <?php foreach ($teamProfiles as $profile): ?>
        <article class="about-team-slide">
          <div class="about-team-profile-card">
            <div class="about-team-profile-banner"></div>
            <div class="about-team-profile-avatar-wrap">
              <img src="<?= htmlspecialchars($profile['avatar']) ?>" alt="<?= htmlspecialchars($profile['name']) ?>" class="about-team-profile-avatar">
            </div>
            <div class="about-team-profile-body">
              <h3><?= htmlspecialchars($profile['name']) ?></h3>
              <div class="about-team-profile-role"><?= htmlspecialchars($profile['role']) ?></div>
              <p><?= htmlspecialchars($profile['summary']) ?></p>
              <div class="about-team-profile-socials">
                <a href="#" class="about-team-profile-social" aria-label="<?= htmlspecialchars($profile['name']) ?> Facebook">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                </a>
                <a href="#" class="about-team-profile-social" aria-label="<?= htmlspecialchars($profile['name']) ?> LinkedIn">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                </a>
                <a href="#" class="about-team-profile-social" aria-label="<?= htmlspecialchars($profile['name']) ?> Instagram">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>
                </a>
              </div>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="about-corp-section" id="latest-news">
    <div class="about-corp-section-head">
      <div>
        <div class="sec-title">Latest News &amp; Events</div>
        <div class="sec-subtitle">Highlights from our supply, service, and customer engagement journey.</div>
      </div>
    </div>
    <div class="about-corp-news-grid">
      <?php foreach ($newsItems as $newsItem): ?>
      <article class="about-corp-news-card">
        <img src="<?= htmlspecialchars($newsItem['image']) ?>" alt="<?= htmlspecialchars($newsItem['title']) ?>" class="about-corp-news-image">
        <div class="about-corp-news-body">
          <div class="about-corp-news-meta"><?= htmlspecialchars($newsItem['date']) ?></div>
          <h3><?= htmlspecialchars($newsItem['title']) ?></h3>
          <p><?= htmlspecialchars($newsItem['description']) ?></p>
          <a href="#contact" class="about-corp-news-link">Read more</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="about-corp-section" id="contact">
    <div class="about-corp-section-head">
      <div>
        <div class="sec-title">Contact Us</div>
        <div class="sec-subtitle">Talk to our team for product enquiries, OEM support, and electronics project requirements.</div>
      </div>
    </div>
    <div class="about-corp-contact-grid about-corp-contact-grid--v2">
      <aside class="about-corp-contact-panel about-corp-contact-panel--v2">
        <div class="about-corp-contact-hero">
          <span class="about-corp-contact-kicker">Quick Response Desk</span>
          <h3>Let us help with your next electronics project.</h3>
          <p>Share your sourcing or engineering requirement and our team will guide you with the right next step.</p>
        </div>
        <div class="about-corp-contact-cards">
          <div class="about-corp-contact-card">
            <div class="about-corp-contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.81 19.79 19.79 0 01.1 2.27 2 2 0 012.07.1h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.16 6.16l.91-.91a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
            </div>
            <div>
              <div class="about-corp-contact-label">Phone</div>
              <div class="about-corp-contact-value about-corp-contact-value--nowrap">+49 (0)8165-9906178</div>
              <div class="about-corp-contact-note">Mon-Fri, 9:00 AM - 6:00 PM CET</div>
            </div>
          </div>
          <div class="about-corp-contact-card">
            <div class="about-corp-contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <div class="about-corp-contact-label">Email</div>
              <div class="about-corp-contact-value">contact@sinelec-tech.com</div>
              <div class="about-corp-contact-note">Typical reply within 24 business hours</div>
            </div>
          </div>
          <div class="about-corp-contact-card">
            <div class="about-corp-contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><path d="M6 9V2h12v20H6v-7"/><polyline points="6 15 2 15 2 9 6 9"/><line x1="10" y1="6" x2="14" y2="6"/><line x1="10" y1="10" x2="14" y2="10"/></svg>
            </div>
            <div>
              <div class="about-corp-contact-label">Fax</div>
              <div class="about-corp-contact-value about-corp-contact-value--nowrap">+49 (0)8165-9039998</div>
              <div class="about-corp-contact-note">For purchase orders and official documents</div>
            </div>
          </div>
          <div class="about-corp-contact-card">
            <div class="about-corp-contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <div class="about-corp-contact-label">Address</div>
              <div class="about-corp-contact-value">Brachvogelweg 9, 85375 Neufahrn, Germany</div>
              <div class="about-corp-contact-note">Serving customers across Europe and global regions</div>
            </div>
          </div>
        </div>
      </aside>

      <div class="about-corp-form-wrap about-corp-form-wrap--v2">
        <div class="about-corp-form-head">
          <h3 class="contact-form-title">Send a Message</h3>
          <p>Tell us what you need and we will respond with the right technical or commercial support.</p>
        </div>
        <form id="contactForm" novalidate>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Your Name</label>
              <input type="text" class="form-inp" placeholder="Aarav Sharma" required>
            </div>
            <div class="form-group">
              <label class="form-label">Company</label>
              <input type="text" class="form-inp" placeholder="Company / Organization">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="tel" class="form-inp" placeholder="+49 (0) 0000 000000" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" class="form-inp" placeholder="you@company.com" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Subject</label>
            <select class="form-inp">
              <option>Product Enquiry</option>
              <option>OEM Design Support</option>
              <option>Chip Programming Service</option>
              <option>Bulk Order Quote</option>
              <option>Technical Support</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea class="form-inp textarea" placeholder="Share your requirement, part numbers, quantity, timeline, and expected support." required></textarea>
          </div>
          <button type="submit" class="btn btn-blue contact-submit-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send Message
          </button>
        </form>
      </div>
    </div>
  </section>

</div>
</main>

<?php require_once 'footer.php'; ?>
