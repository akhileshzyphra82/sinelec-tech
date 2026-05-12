<?php
require_once '../data/store_data.php';
$currentPage = 'resources';
$pageTitle   = 'Shipping and Payment Terms — Sinelec Tech';
require_once 'header.php';

$shippingRows = [
    [
        'region' => 'Germany',
        'cost' => '4,99€',
        'service' => 'DPD',
        'payments' => ['Paypal', 'Credit Card via Paypal (No Paypal Account needed)', 'Bank Transfer', 'Invoice (Corporate customers)'],
        'stock' => '1-2 Workdays',
        'outStock' => '4-5 Workdays',
    ],
    [
        'region' => 'Austria, Belgium, Bulgaria, Croatia, Czech Republic, Denmark, Estonia, Finland, France, Hungary, Ireland, Italy, Latvia, Lithuania, Luxembourg, Malta, Monaco, Netherlands, Poland, Portugal, Romania, Slovakia, Slovenia, Spain, Sweden and the UK',
        'cost' => '12,99€',
        'service' => 'DPD / UPS',
        'payments' => ['Paypal', 'Credit Card via Paypal (No Paypal Account needed)', 'Bank Transfer', 'Invoice (Corporate customers)'],
        'stock' => '2-3 Workdays',
        'outStock' => '4-7 Workdays',
    ],
    [
        'region' => 'Switzerland',
        'cost' => '19,99€',
        'service' => 'UPS',
        'payments' => ['Paypal', 'Credit Card via Paypal (No Paypal Account needed)', 'Bank Transfer', 'Invoice (Corporate customers)'],
        'stock' => '2-4 Workdays',
        'outStock' => '4-7 Workdays',
    ],
    [
        'region' => 'Rest of the World',
        'cost' => '19,99€',
        'service' => 'UPS',
        'payments' => ['Paypal', 'Credit Card via Paypal (No Paypal Account needed)', 'Bank Transfer', 'Invoice (Corporate customers)'],
        'stock' => '7-10 Workdays',
        'outStock' => '11-14 Workdays',
    ],
];
?>

<main>
  <style>
    .spt-banner {
      position: relative;
      min-height: 220px;
      display: flex;
      align-items: center;
      background-image:
        linear-gradient(90deg, rgba(2, 20, 36, .78) 0%, rgba(3, 37, 68, .72) 45%, rgba(4, 30, 54, .66) 100%),
        url('https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1920&q=80');
      background-size: cover;
      background-position: center;
    }
    .spt-banner-inner {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      color: #fff;
    }
    .spt-banner-title {
      margin: 0;
      font-size: 48px;
      line-height: 1.04;
      letter-spacing: .01em;
      font-weight: 800;
      text-transform: uppercase;
    }
    .spt-banner-crumb {
      margin: 0;
      padding: 0;
      list-style: none;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .spt-banner-crumb li { color: rgba(255,255,255,.88); }
    .spt-banner-crumb li + li::before {
      content: "/";
      color: rgba(255,255,255,.74);
      margin-right: 8px;
    }
    .spt-banner-crumb a { color: #fff; }
    .spt-content {
      background: #fff;
      padding: 42px 0 58px;
    }
    .spt-prose {
      max-width: 1280px;
      color: #202a35;
      font-size: 15px;
      line-height: 1.6;
    }
    .spt-prose h2 {
      margin: 0 0 12px;
      font-size: 38px;
      line-height: 1.15;
      font-weight: 800;
      color: #111c2a;
    }
    .spt-prose h3 {
      margin: 24px 0 8px;
      font-size: 26px;
      line-height: 1.2;
      font-weight: 800;
      color: #111c2a;
    }
    .spt-prose p {
      margin: 0 0 14px;
      font-size: 15px;
      line-height: 1.62;
      color: #2b3745;
    }
    .spt-prose ul,
    .spt-prose ol {
      margin: 0 0 16px 22px;
      padding: 0;
      display: grid;
      gap: 7px;
    }
    .spt-prose li {
      font-size: 15px;
      line-height: 1.6;
      color: #2b3745;
    }
    .spt-prose a {
      color: #1f5fa8;
      text-decoration: none;
    }
    .spt-prose a:hover { text-decoration: underline; }
    .spt-table-wrap {
      margin-top: 12px;
      border: 1px solid #d5deea;
      overflow: auto;
    }
    .spt-table {
      width: 100%;
      min-width: 980px;
      border-collapse: collapse;
    }
    .spt-table th,
    .spt-table td {
      border: 1px solid #d5deea;
      padding: 10px;
      text-align: left;
      vertical-align: top;
      font-size: 13px;
      line-height: 1.5;
      color: #2d3a48;
      background: #fff;
    }
    .spt-table th {
      background: #0f1013;
      color: #fff;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: .01em;
    }
    .spt-table ul { margin: 0; padding-left: 15px; display: grid; gap: 5px; }
    .spt-table ul li { font-size: 13px; line-height: 1.45; }
    @media (max-width: 1024px) {
      .spt-banner-title { font-size: 34px; }
      .spt-prose h2 { font-size: 32px; }
      .spt-prose h3 { font-size: 24px; }
    }
    @media (max-width: 760px) {
      .spt-banner { min-height: 160px; }
      .spt-banner-inner { flex-direction: column; align-items: flex-start; justify-content: center; }
      .spt-banner-title { font-size: 23px; }
      .spt-banner-crumb { font-size: 10px; }
      .spt-content { padding: 26px 0 38px; }
      .spt-prose { font-size: 13px; line-height: 1.55; }
      .spt-prose h2 { font-size: 24px; margin-bottom: 10px; }
      .spt-prose h3 { font-size: 19px; margin-top: 18px; }
      .spt-prose p,
      .spt-prose li { font-size: 13px; line-height: 1.58; }
      .spt-table th,
      .spt-table td { font-size: 12px; padding: 8px; }
    }
  </style>

  <section class="spt-banner">
    <div class="wrap spt-banner-inner">
      <h1 class="spt-banner-title">Shipping and Payment</h1>
      <ol class="spt-banner-crumb">
        <li><a href="index">Home</a></li>
        <li><a href="#">Company</a></li>
        <li>Shipping and Payment</li>
      </ol>
    </div>
  </section>

  <section class="spt-content">
    <div class="wrap">
      <div class="spt-prose">
        <h2>Shipping and Payment</h2>
        <p>We always try to process orders as quickly as possible. When all ordered products are in stock and the order is placed before 14.00 hours (CET) on a business day, the order will be shipped the same day. In some cases we might not be able to process your order immediately. An employee will contact you in that case.</p>
        <p>You will receive at least two emails from us with regards to your order:</p>
        <ol>
          <li><strong>After placing your order.</strong> We will send you an email confirming your order and copy of Invoice.</li>
          <li><strong>When your order is shipped.</strong> We will send you an Email with tracking code and a soft copy of Invoice.</li>
        </ol>
        <p>You can place your order through the webshop or can ask us for a quotation (<a href="request-a-quote" target="_blank">Request Quote</a>).</p>

        <h3>Payment Information</h3>
        <p>We accept the following payment methods in our webshop:</p>
        <ol>
          <li>Paypal</li>
          <li>Credit Card via Paypal (No Paypal Account Needed)</li>
        </ol>
        <p>Additionally, if you are buying through a quotation provided by us or through your Company’s Purchase order, you can also pay through:</p>
        <ol>
          <li>Bank Transfer in our German Bank Account</li>
          <li>Invoice (Only for Corporate Customer)</li>
          <li>Paypal</li>
        </ol>

        <h3>Foreign EU company VAT exempted ordering</h3>
        <p>Do you order for a company in the EU (except Germany) and you have a valid EU VAT number? then German VAT do not apply. To order VAT free you have to supply your valid EU VAT number while ordering on our webshop or while requesting for a quotation. If you provided a valid EU VAT number in our webshop, you'll find that VAT is not added to your order amount.</p>
        <p>Please note that if these instructions aren't followed properly and an order is placed with invalid EU VAT number we will charge 5€ administrative costs to correct this afterwards.</p>
        <p><strong>Follow these steps:</strong></p>
        <ol>
          <li><a href="#" data-auth-open="signup" title="Create an account">Create an account</a></li>
          <li>Enter your valid EU VAT number while ordering through our Webshop.</li>
          <li>You can place your order now. You will see the webshop doesn't calculate taxes anymore.</li>
          <li>OR if you are buying through our quotation (<a href="request-a-quote" title="Request Quote" target="_blank">Request Quote</a>) or your companys Purchase Order. Then provide us your Valid EU VAT Number and we will send you a VAT free Quotation and/or Invoice.</li>
        </ol>

        <h3>International customers outside the EU</h3>
        <p>Are you a international customer living/shipping outside the EU? German taxes don't apply then. If you create an account with address outside of EU, then end order prices will be displayed excluding German VAT.</p>
        <p>Taxes may be calculated when your order passes customs in your country.</p>

        <h3>Shipping Cost and Delivery Information</h3>
        <div class="spt-table-wrap">
          <table class="spt-table">
            <thead>
              <tr>
                <th>Country</th>
                <th>Shipping Cost</th>
                <th>Shipping Service</th>
                <th>Payment Methods</th>
                <th>Delivery Time (In Stock Product)</th>
                <th>Delivery Time (Out of Stock Product)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shippingRows as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['region']) ?></td>
                <td><?= htmlspecialchars($row['cost']) ?></td>
                <td><?= htmlspecialchars($row['service']) ?></td>
                <td>
                  <ul>
                    <?php foreach ($row['payments'] as $payment): ?>
                    <li><?= htmlspecialchars($payment) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </td>
                <td><?= htmlspecialchars($row['stock']) ?></td>
                <td><?= htmlspecialchars($row['outStock']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require_once 'footer.php'; ?>
