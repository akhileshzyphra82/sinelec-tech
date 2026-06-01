<?php
require_once __DIR__ . '/common/functions.php';
require_once __DIR__ . '/controller/website_controller.php';

$_kCtrl = new WebsiteController();

$_dbCats = $_kCtrl->getAllCategoriesFlat();
$categories = array_map(fn($c) => [
    'id'   => (int)(float)($c->PRODUCT_CATEGORY_ID ?? 0),
    'name' => (string)($c->PRODUCT_CATEGORY_NAME ?? ''),
], $_dbCats);
$categoryNames = array_map(fn($c) => $c['name'], $categories);

$_dbMfrs = $_kCtrl->getAllManufacturers();
$manufacturers = array_map(fn($m) => [
    'id'   => (int)(float)($m->MANUFACTURER_ID ?? 0),
    'name' => (string)($m->NAME ?? ''),
], $_dbMfrs);
$manufacturerNames = array_map(fn($m) => $m['name'], $manufacturers);

$products = []; /* Products now served via AJAX catalog — not needed here */
$featuredProducts = []; /* No static featured products — show none */

unset($_kCtrl, $_dbCats, $_dbMfrs);

$sinelaKnowledge = [
    'assistant' => [
        'name' => 'Sinela AI',
        'tagline' => 'Electronics sourcing and support assistant',
        'greeting' => "Hi, I'm Sinela AI 👋 How can I help you today?",
        'online_label' => 'Online now',
        'placeholder' => 'Ask about products, quotes, delivery, or chip programming...',
        'send_label' => 'Send',
        'minimize_label' => 'Minimize chat',
        'open_label' => 'Open Sinela AI chatbot',
        'empty_state' => 'Choose a suggested question or type your own.',
    ],
    'company' => [
        'name' => 'Sinelec Technologies',
        'website' => 'https://new.sinelec-tech.com/',
        'business' => 'Semiconductor and electronic component e-commerce store',
        'summary' => 'Sinelec Technologies supplies semiconductors, electronic components, bulk-order support, engineering services, PCB support, and custom chip programming for OEMs, R&D teams, startups, and industrial buyers.',
        'address' => 'Brachvogelweg 9, 85375 Neufahrn, Germany',
        'support_hours' => 'Monday to Saturday, 9 AM to 6 PM',
        'email' => 'contact@sinelec-tech.com',
        'quote_email' => 'info@sinelec-tech.com',
        'phone' => '+49 (0)8165-9906178',
        'whatsapp' => '+91-9876543210',
        'whatsapp_link' => 'https://wa.me/919876543210?text=Hi%20Sinelec%20Technologies,%20I%20want%20to%20get%20an%20instant%20quote.',
    ],
    'suggested_questions' => [
        'What products do you sell?',
        'Do you provide chip programming service?',
        'How can I get an instant quote?',
        'Do you support bulk orders?',
        'What brands or manufacturers do you offer?',
        'What is the chip programming turnaround time?',
        'Do you provide PCB design or assembly service?',
        'What are your delivery options?',
        'What is your return policy?',
        'How can I contact support?',
    ],
    'catalog' => [
        'categories' => $categories,
        'category_names' => $categoryNames,
        'manufacturers' => $manufacturers,
        'manufacturer_names' => $manufacturerNames,
        'featured_products' => $featuredProducts,
        'product_summary' => 'We sell microcontrollers, logic ICs, op-amps and comparators, power management ICs, transistors and MOSFETs, sensors and modules, communication ICs, memory ICs, passive components, and display and LED products.',
    ],
    'services' => [
        'chip_programming' => [
            'title' => 'Custom chip programming',
            'starting_price' => '₹499 per chip',
            'turnaround' => '48-hour turnaround',
            'formats' => ['.hex', '.bin', '.elf', '.srec'],
            'platforms' => ['Arduino', 'STM32', 'PIC', 'AVR', 'ESP32', 'nRF52', 'RP2040', 'MSP430', 'RISC-V'],
            'summary' => 'Sinelec provides custom chip programming for customer-supplied or Sinelec-supplied MCUs. You can share your firmware over email or WhatsApp, and the team programs, verifies, and dispatches the chips.',
        ],
        'pcb_support' => [
            'summary' => 'Sinelec supports PCB design guidance, PCB layout support, component sourcing, and PCB assembly coordination as part of its electronics manufacturing and engineering support offering.',
        ],
        'component_sourcing' => [
            'summary' => 'Sinelec helps with component sourcing, availability matching, OEM sourcing programs, and urgent procurement requirements across semiconductor and embedded electronics projects.',
        ],
        'bulk_orders' => [
            'discount' => 'Up to 40% off on bulk orders',
            'summary' => 'Bulk and OEM buyers can request custom pricing. Volume discounts start at 10+ units for many products and can go up to 40% off for larger production quantities.',
        ],
    ],
    'commerce' => [
        'quote_process' => [
            'steps' => [
                'Choose product category and product or part number.',
                'Enter quantity and contact details.',
                'Share delivery details and any notes like firmware, timeline, or compliance requirements.',
                'Submit the request or use WhatsApp for urgent quotes.',
                'Sinelec reviews the requirement and responds with pricing and support details.',
            ],
        ],
        'order_process' => [
            'steps' => [
                'Browse products or request a quote for bulk and project requirements.',
                'Confirm pricing, quantity, and delivery details.',
                'Complete payment using the available payment methods.',
                'Orders placed before 2 PM can qualify for same-day dispatch when in stock.',
                'Track delivery and coordinate with support for project-specific needs.',
            ],
        ],
        'delivery' => [
            'same_day_dispatch' => 'Same-day dispatch for orders placed before 2 PM',
            'delivery_window' => 'Typical delivery is 1 to 4 business days depending on location for in-stock orders.',
            'free_delivery' => 'Free delivery on orders ₹5,000+',
        ],
        'returns' => [
            'policy' => '7-day return policy',
            'summary' => 'Customers can contact support within 7 days for return guidance. The site also highlights free returns and genuine product assurance.',
        ],
        'payments' => [
            'methods' => ['PayPal', 'Bank Transfer', 'Visa', 'Mastercard', 'American Express'],
        ],
    ],
    'support' => [
        'contact' => [
            'phone' => '+49 (0)8165-9906178',
            'whatsapp' => '+91-9876543210',
            'email' => 'contact@sinelec-tech.com',
            'quote_email' => 'info@sinelec-tech.com',
            'address' => 'Brachvogelweg 9, 85375 Neufahrn, Germany',
            'hours' => 'Monday to Saturday, 9 AM to 6 PM',
        ],
        'fallback' => [
            'message' => "I’m sorry, I don’t have enough information about that. Please connect with our live support team on WhatsApp for more details.",
            'whatsapp_label' => 'Chat on WhatsApp',
            'phone_label' => 'Call support',
            'email_label' => 'Email support',
        ],
    ],
    'intents' => [
        [
            'id' => 'products',
            'question' => 'What products do you sell?',
            'keywords' => ['products', 'sell', 'catalog', 'categories', 'semiconductor', 'components', 'microcontrollers', 'sensors'],
            'answer' => [
                'text' => 'Sinelec sells a broad semiconductor and electronics catalog for development, sourcing, and production needs.',
                'bullets' => [
                    'Microcontrollers',
                    'Logic ICs',
                    'Op-Amps and comparators',
                    'Power management ICs',
                    'Transistors and MOSFETs',
                    'Sensors and modules',
                    'Communication ICs',
                    'Memory ICs',
                    'Passive components',
                    'Display and LED products',
                ],
                'links' => [
                    ['label' => 'Browse products', 'href' => 'products'],
                ],
            ],
        ],
        [
            'id' => 'chip_programming',
            'question' => 'Do you provide chip programming service?',
            'keywords' => ['chip programming', 'programming', 'firmware', 'flash', 'mcu programming', 'hex', 'bin'],
            'answer' => [
                'text' => 'Yes. Sinelec provides custom chip programming services for customer-supplied or Sinelec-supplied devices.',
                'bullets' => [
                    'Starting price: ₹499 per chip',
                    'Typical turnaround: 48 hours',
                    'Supported examples: Arduino, STM32, PIC, AVR, ESP32',
                    'Firmware formats accepted: .hex, .bin, .elf, .srec',
                ],
                'links' => [
                    ['label' => 'View chip programming service', 'href' => 'chip-programming'],
                    ['label' => 'Request a quote', 'href' => 'request-a-quote'],
                ],
            ],
        ],
        [
            'id' => 'instant_quote',
            'question' => 'How can I get an instant quote?',
            'keywords' => ['instant quote', 'quote', 'quotation', 'price', 'pricing', 'whatsapp quote'],
            'answer' => [
                'text' => 'For the fastest response, use WhatsApp for instant quote requests or submit the request-a-quote form for detailed commercial support.',
                'bullets' => [
                    'Use the WhatsApp quote button for urgent and bulk enquiries',
                    'Share part number, quantity, and delivery location',
                    'You can also submit multiple products in the Request a Quote form',
                ],
                'links' => [
                    ['label' => 'Open Request a Quote', 'href' => 'request-a-quote'],
                ],
            ],
        ],
        [
            'id' => 'bulk_orders',
            'question' => 'Do you support bulk orders?',
            'keywords' => ['bulk', 'bulk orders', 'volume pricing', 'oem', 'production', '100+', 'discount'],
            'answer' => [
                'text' => 'Yes. Sinelec supports bulk orders, OEM requirements, and repeat production purchases.',
                'bullets' => [
                    'Volume discounts start at 10+ units for many products',
                    'Bulk-order savings can go up to 40% off',
                    'Dedicated quote handling supports BOM-based and urgent sourcing requests',
                ],
                'links' => [
                    ['label' => 'Request bulk pricing', 'href' => 'request-a-quote'],
                ],
            ],
        ],
        [
            'id' => 'brands',
            'question' => 'What brands or manufacturers do you offer?',
            'keywords' => ['brands', 'manufacturers', 'manufacturer', 'brand list', 'stmicroelectronics', 'texas instruments'],
            'answer' => [
                'text' => 'Sinelec works with multiple semiconductor manufacturers and distributes genuine components.',
                'bullets' => [
                    'Examples include STMicroelectronics, Texas Instruments, Microchip Technology, Infineon, Vishay, Maxim Integrated, Nordic Semiconductor, and Espressif Systems',
                    'The manufacturers directory currently lists ' . count($manufacturerNames) . '+ brands',
                ],
                'links' => [
                    ['label' => 'View manufacturers', 'href' => 'manufacturers'],
                ],
            ],
        ],
        [
            'id' => 'turnaround',
            'question' => 'What is the chip programming turnaround time?',
            'keywords' => ['turnaround', 'lead time', 'chip programming turnaround', '48 hour', '48-hour'],
            'answer' => [
                'text' => 'The site highlights a 48-hour turnaround for chip programming projects.',
                'bullets' => [
                    'Programmed and tested chips are typically dispatched within 24 to 48 hours after receiving firmware',
                    'Urgent requirements can be coordinated through WhatsApp or the quote team',
                ],
                'links' => [
                    ['label' => 'Talk to the quote team', 'href' => 'request-a-quote'],
                ],
            ],
        ],
        [
            'id' => 'pcb_services',
            'question' => 'Do you provide PCB design or assembly service?',
            'keywords' => ['pcb', 'assembly', 'pcb design', 'pcb assembly', 'layout', 'manufacturing'],
            'answer' => [
                'text' => 'Yes. Sinelec presents PCB design, PCB layout support, PCB assembly coordination, and component sourcing as part of its engineering and manufacturing support offering.',
                'bullets' => [
                    'PCB design and layout support',
                    'Component sourcing for builds',
                    'Assembly and production-oriented coordination',
                    'Chip programming and embedded support for complete project execution',
                ],
                'links' => [
                    ['label' => 'Request project support', 'href' => 'request-a-quote'],
                    ['label' => 'Learn about services', 'href' => 'chip-programming'],
                ],
            ],
        ],
        [
            'id' => 'delivery',
            'question' => 'What are your delivery options?',
            'keywords' => ['delivery', 'shipping', 'dispatch', 'same day', 'free delivery', 'courier'],
            'answer' => [
                'text' => 'Sinelec offers fast dispatch and delivery support for in-stock products.',
                'bullets' => [
                    'Orders before 2 PM can be dispatched the same day',
                    'Typical in-stock delivery window: 1 to 4 business days depending on location',
                    'Free delivery is available on orders ₹5,000+',
                ],
            ],
        ],
        [
            'id' => 'returns',
            'question' => 'What is your return policy?',
            'keywords' => ['return', 'returns', 'refund', 'replacement', 'policy'],
            'answer' => [
                'text' => 'Sinelec highlights a 7-day return policy along with genuine-product assurance.',
                'bullets' => [
                    '7-day return policy',
                    'Support can guide you through return and replacement questions',
                ],
            ],
        ],
        [
            'id' => 'support',
            'question' => 'How can I contact support?',
            'keywords' => ['contact', 'support', 'phone', 'email', 'address', 'whatsapp', 'hours'],
            'answer' => [
                'text' => 'You can contact Sinelec through phone, WhatsApp, or email.',
                'bullets' => [
                    'Phone: +49 (0)8165-9906178',
                    'WhatsApp: +91-9876543210',
                    'Email: contact@sinelec-tech.com',
                    'Support hours: Monday to Saturday, 9 AM to 6 PM',
                    'Address: Brachvogelweg 9, 85375 Neufahrn, Germany',
                ],
                'links' => [
                    ['label' => 'About and contact details', 'href' => 'about#contact'],
                ],
            ],
        ],
        [
            'id' => 'payment',
            'question' => 'What payment methods do you accept?',
            'keywords' => ['payment', 'paypal', 'visa', 'mastercard', 'amex', 'bank transfer', 'payment methods'],
            'answer' => [
                'text' => 'The site lists multiple accepted payment methods for checkout and order processing.',
                'bullets' => [
                    'PayPal',
                    'Bank Transfer',
                    'Visa',
                    'Mastercard',
                    'American Express',
                ],
            ],
        ],
        [
            'id' => 'quote_process',
            'question' => 'How does the quote process work?',
            'keywords' => ['quote process', 'request quote', 'how to quote', 'quotation process'],
            'answer' => [
                'text' => 'The quote process is set up for both single-product and multi-product enquiries.',
                'bullets' => [
                    'Select the category and product or part number',
                    'Enter quantity and contact details',
                    'Add delivery details and project notes',
                    'Submit the form or use WhatsApp for faster quote handling',
                ],
                'links' => [
                    ['label' => 'Open the quote form', 'href' => 'request-a-quote'],
                ],
            ],
        ],
    ],
];

return $sinelaKnowledge;
