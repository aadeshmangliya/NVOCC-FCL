-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2026 at 09:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shipment_tracking`
--

-- --------------------------------------------------------

--
-- Table structure for table `arrival_notices`
--

CREATE TABLE `arrival_notices` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_user_id` int(10) UNSIGNED DEFAULT NULL,
  `staff_name` varchar(255) DEFAULT NULL,
  `staff_email` varchar(255) DEFAULT NULL,
  `shipment_id` int(10) UNSIGNED DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `consignee_name` varchar(255) DEFAULT NULL,
  `consignee_email` varchar(255) DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `free_days_allowed` int(11) DEFAULT 0,
  `notice_message` text DEFAULT NULL,
  `status` enum('draft','sent') NOT NULL DEFAULT 'draft',
  `email_status` varchar(50) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `send_count` int(10) UNSIGNED DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cargo_release_records`
--

CREATE TABLE `cargo_release_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `shipment_id` int(10) UNSIGNED NOT NULL,
  `cargo_release_status` varchar(30) NOT NULL DEFAULT 'pending',
  `cargo_release_reason` text DEFAULT NULL,
  `cargo_release_date` date DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `company_key` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `national_identity_no` varchar(50) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `carrier_name` varchar(200) DEFAULT NULL,
  `logo_path` varchar(225) DEFAULT NULL,
  `color_primary` varchar(7) DEFAULT '#006a6a',
  `color_secondary` varchar(7) DEFAULT '#d45f00',
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_key`, `name`, `display_name`, `address`, `phone`, `email`, `national_identity_no`, `website`, `carrier_name`, `logo_path`, `color_primary`, `color_secondary`, `is_active`, `created_at`, `updated_at`) VALUES
(10, 'INDUS_MAGNA', 'INDUS MAGNA SHIPPING', 'INDUS MAGNA SHIPPING', NULL, NULL, NULL, NULL, 'https://indusmagnashipping.com/', 'https://indusmagnashipping.com/', 'http://localhost/wpcargo/assets/images/logo/logo_1791018240.png', '#37afe6', '#ce9f36', 1, '2026-10-03 09:04:00', '2026-10-03 09:04:00');

-- --------------------------------------------------------

--
-- Table structure for table `document_company_assignments`
--

CREATE TABLE `document_company_assignments` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `is_default` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_company_assignments`
--

INSERT INTO `document_company_assignments` (`id`, `document_id`, `company_id`, `is_default`, `created_at`, `updated_at`) VALUES
(6, 7, 5, 0, '2026-06-18 11:32:05', '2026-06-18 11:32:05'),
(7, 9, 5, 0, '2026-06-18 11:35:48', '2026-06-18 11:35:48'),
(18, 11, 10, 0, '2026-10-03 11:22:17', '2026-10-03 11:22:17'),
(17, 9, 10, 0, '2026-10-03 11:22:17', '2026-10-03 11:22:17'),
(20, 7, 10, 0, '2026-10-03 11:22:17', '2026-10-03 11:22:17'),
(19, 12, 10, 0, '2026-10-03 11:22:17', '2026-10-03 11:22:17'),
(16, 10, 10, 0, '2026-10-03 11:22:17', '2026-10-03 11:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `document_templates`
--

CREATE TABLE `document_templates` (
  `id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `html_template` text NOT NULL,
  `is_default` tinyint(4) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_templates`
--

INSERT INTO `document_templates` (`id`, `document_type`, `template_name`, `html_template`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(7, 'bill_of_lading', 'Royal Gulf Bill of Lading', '<!DOCTYPE html>\n<html lang=\"en\">\n    <head>\n        <meta charset=\"UTF-8\">\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, user-scalable=yes\">\n        <title>Bill of Lading | {{company_display_name}}</title>\n        <style>\n    * {\n      margin: 0;\n      padding: 0;\n      box-sizing: border-box;\n    }\n\n    body {\n      background: #e2e8f0;\n      font-family: \'Inter\', \'Segoe UI\', \'Roboto\', system-ui, -apple-system, sans-serif;\n      padding: 2rem 1.5rem;\n      color: #0a1c2f;\n    }\n\n    /* main document container */\n    .bol-document {\n      max-width: 1280px;\n      margin: 0 auto;\n      background: white;\n      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);\n      border-radius: 4px;\n      transition: all 0.2s;\n    }\n\n    /* inner padding structure (classic bill of lading spacing) */\n    .bol-inner {\n      padding: 2rem 2rem 1.8rem 2rem;\n    }\n\n    /* top branding + title row */\n    .top-row {\n      display: flex;\n      justify-content: space-between;\n      align-items: flex-start;\n      flex-wrap: wrap;\n      border-bottom: 2px solid {{color_primary}};\n      padding-bottom: 1.25rem;\n      margin-bottom: 1.5rem;\n    }\n\n    .shipper-logo h2 {\n      font-size: 1.6rem;\n      font-weight: 700;\n      letter-spacing: -0.3px;\n      color: {{color_primary}};\n      margin-bottom: 0.2rem;\n    }\n    .shipper-logo .sub {\n      font-size: 0.7rem;\n      color: #3b6e8b;\n      font-weight: 500;\n      letter-spacing: 0.3px;\n    }\n    .title-area {\n      text-align: right;\n    }\n    .title-area h1 {\n      font-size: 2rem;\n      font-weight: 800;\n      color: {{color_primary}};\n      letter-spacing: 1px;\n    }\n    .title-area .bl-original {\n      font-size: 0.7rem;\n      background: {{color_primary}};\n      color: white;\n      display: inline-block;\n      padding: 0.2rem 0.8rem;\n      border-radius: 30px;\n      margin-top: 6px;\n      font-weight: 600;\n    }\n\n    /* grid layout for forms (2-col, 3-col) */\n    .two-col-grid {\n      display: grid;\n      grid-template-columns: 1fr 1fr;\n      gap: 1.2rem;\n      margin-bottom: 1.2rem;\n    }\n    .three-col-grid {\n      display: grid;\n      grid-template-columns: repeat(3, 1fr);\n      gap: 1.2rem;\n      margin-bottom: 1.2rem;\n    }\n    .four-col-grid {\n      display: grid;\n      grid-template-columns: repeat(4, 1fr);\n      gap: 1rem;\n      margin-bottom: 1.2rem;\n    }\n\n    /* field boxes — classic shipping document style */\n    .field-group {\n      border: 1px solid #cbd5e1;\n      background: #ffffff;\n      padding: 0.65rem 1rem;\n      border-radius: 0px;\n      transition: 0.1s;\n      box-shadow: 0 1px 1px rgba(0,0,0,0.02);\n    }\n    .field-label {\n      font-size: 0.7rem;\n      font-weight: 700;\n      text-transform: uppercase;\n      letter-spacing: 0.5px;\n      color: #2c5a7a;\n      margin-bottom: 0.35rem;\n    }\n    .field-value {\n      font-size: 0.85rem;\n      font-weight: 600;\n      color: #0f2c3f;\n      line-height: 1.35;\n      word-break: break-word;\n    }\n    .field-sub {\n      font-size: 0.7rem;\n      color: #5e6f8d;\n      margin-top: 4px;\n      font-weight: 400;\n    }\n    .text-uppercase {\n      text-transform: uppercase;\n    }\n    .highlight-number {\n      font-size: 0.95rem;\n      font-weight: 800;\n      color: {{color_secondary}};\n      letter-spacing: 0.3px;\n    }\n    .divider-light {\n      height: 1px;\n      background: #e2e8f0;\n      margin: 1rem 0 1.2rem 0;\n    }\n\n    /* cargo table */\n    .cargo-section {\n      margin: 1.5rem 0 1.3rem 0;\n    }\n    .section-title {\n      font-size: 0.85rem;\n      font-weight: 800;\n      text-transform: uppercase;\n      background: {{color_primary}};\n      color: white;\n      padding: 0.5rem 1rem;\n      letter-spacing: 1px;\n      margin-bottom: 0;\n      border-radius: 2px;\n    }\n    .cargo-table {\n      width: 100%;\n      border-collapse: collapse;\n      font-size: 0.8rem;\n      border: 1px solid #cfdfed;\n    }\n    .cargo-table th {\n      background: #f1f5f9;\n      text-align: left;\n      padding: 0.75rem 0.6rem;\n      font-weight: 700;\n      color: {{color_primary}};\n      border-bottom: 1px solid #cbd5e1;\n      font-size: 0.7rem;\n      letter-spacing: 0.4px;\n    }\n    .cargo-table td {\n      padding: 0.7rem 0.6rem;\n      border-bottom: 1px solid #e9edf2;\n      vertical-align: top;\n      color: #1a2c3e;\n    }\n    .cargo-table tr:last-child td {\n      border-bottom: none;\n    }\n    .total-row {\n      background: #f8fafc;\n      font-weight: 700;\n      border-top: 1px solid #cbd5e1;\n    }\n    .goods-desc-small {\n      font-size: 0.75rem;\n      font-weight: 500;\n      color: #0f2c3f;\n    }\n    .hs-code-text {\n      font-size: 0.7rem;\n      color: #3a6b92;\n      margin-top: 4px;\n    }\n    .freight-term {\n      display: inline-block;\n      background: #eef2ff;\n      font-size: 0.65rem;\n      padding: 2px 8px;\n      border-radius: 12px;\n      font-weight: 700;\n      margin-top: 5px;\n    }\n\n    /* signatures and foot */\n    .footer-grid {\n      display: grid;\n      grid-template-columns: 2fr 1fr;\n      gap: 1.8rem;\n      margin-top: 1.5rem;\n      border-top: 1px solid #cfdfed;\n      padding-top: 1.5rem;\n    }\n    .terms-text {\n      font-size: 0.7rem;\n      color: #334155;\n      line-height: 1.45;\n    }\n    .sign-block {\n      text-align: left;\n    }\n    .sign-line {\n      display: flex;\n      justify-content: space-between;\n      margin-top: 1rem;\n      font-size: 0.7rem;\n      font-weight: 700;\n      color: #2c5a7a;\n      border-top: 1px solid #b9cadb;\n      padding-top: 0.6rem;\n    }\n    .print-btn {\n      text-align: center;\n      background: #f1f5f9;\n      padding: 1rem;\n      border-top: 1px solid #ddd;\n    }\n    .btn-print {\n      background: {{color_primary}};\n      border: none;\n      color: white;\n      font-weight: 600;\n      padding: 0.6rem 2rem;\n      font-size: 0.8rem;\n      border-radius: 40px;\n      cursor: pointer;\n      transition: 0.2s;\n      box-shadow: 0 1px 2px rgba(0,0,0,0.1);\n    }\n    .btn-print:hover {\n      background: {{color_secondary}};\n    }\n\n    @media print {\n      body {\n        background: white;\n        padding: 0;\n        margin: 0;\n      }\n      .bol-document {\n        box-shadow: none;\n        margin: 0;\n        border-radius: 0;\n      }\n      .btn-print, .print-btn {\n        display: none;\n      }\n      .field-group {\n        break-inside: avoid;\n        border: 1px solid #ccc;\n      }\n      .cargo-table th {\n        background: #eef2ff !important;\n        -webkit-print-color-adjust: exact;\n        print-color-adjust: exact;\n      }\n      .section-title {\n        background: {{color_primary}} !important;\n        -webkit-print-color-adjust: exact;\n        print-color-adjust: exact;\n      }\n    }\n\n    /* responsive fixes */\n    @media (max-width: 780px) {\n      .two-col-grid, .three-col-grid, .four-col-grid {\n        grid-template-columns: 1fr;\n        gap: 0.8rem;\n      }\n      .bol-inner {\n        padding: 1.2rem;\n      }\n      .footer-grid {\n        grid-template-columns: 1fr;\n      }\n    }\n    .small-note {\n      font-size: 0.65rem;\n      color: #4a5b6e;\n      margin-top: 3px;\n    }\n        </style>\n    </head>\n    <body>\n        <div class=\"bol-document\">\n            <div class=\"bol-inner\">\n                <!-- HEADER: Shipper / B/L title -->\n                <div class=\"top-row\">\n                    <div class=\"shipper-logo\">\n                        <h2>{{company_display_name}}</h2>\n                        <div class=\"sub\">{{company_tagline}}</div>\n                        <div class=\"small-note\" style=\"margin-top: 6px;\">{{company_established}}</div>\n                    </div>\n                    <div class=\"title-area\">\n                        <h1>BILL OF LADING</h1>\n                        <div class=\"bl-original\">{{bl_type}}</div>\n                        <div class=\"small-note\" style=\"margin-top: 6px;\">{{incoterms}} · Terms {{incoterms_version}}</div>\n                    </div>\n                </div>\n                <!-- B/L number + booking row (two col) -->\n                <div class=\"two-col-grid\">\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">BILL OF LADING NO.</div>\n                        <div class=\"field-value highlight-number\">{{tracking_number}}</div>\n                        <div class=\"field-sub\">Carrier\'s reference</div>\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">BOOKING REF / S/O NO.</div>\n                        <div class=\"field-value\">{{booking_no}}</div>\n                        <div class=\"field-sub\">Export Ref: {{export_reference}}</div>\n                    </div>\n                </div>\n                <!-- Shipper + Consignee primary -->\n                <div class=\"two-col-grid\">\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">SHIPPER (EXPORTER / FULL NAME)</div>\n                        <div class=\"field-value\">{{shipper_name}}</div>\n                        <div class=\"field-sub\">{{shipper_address}}</div>\n                        {{#if shipper_tax_id}}\n                        <div class=\"field-sub\">NTN: {{shipper_tax_id}} | GST: {{shipper_gst}}</div>\n                        {{/if}}\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">CONSIGNEE (TO ORDER / NAMED)</div>\n                        <div class=\"field-value\">{{consignee_name}}</div>\n                        <div class=\"field-sub\">{{consignee_address}}</div>\n                        {{#if consignee_tax_id}}\n                        <div class=\"field-sub\">TRN: {{consignee_tax_id}}</div>\n                        {{/if}}\n                    </div>\n                </div>\n                <!-- Notify party + forwarding agent -->\n                <div class=\"two-col-grid\">\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">NOTIFY PARTY</div>\n                        <div class=\"field-value\">{{notify_name}}</div>\n                        <div class=\"field-sub\">{{notify_address}}</div>\n                        {{#if notify_phone}}\n                        <div class=\"field-sub\">Attn: {{notify_contact}} / Tel: {{notify_phone}}</div>\n                        {{/if}}\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">FORWARDING AGENT / BROKER</div>\n                        <div class=\"field-value\">{{forwarding_agent}}</div>\n                        <div class=\"field-sub\">{{forwarding_agent_address}}</div>\n                    </div>\n                </div>\n                <!-- Vessel & voyage information -->\n                <div class=\"three-col-grid\">\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">PORT OF LOADING (POL)</div>\n                        <div class=\"field-value\">{{pol}}</div>\n                        {{#if etd}}\n                        <div class=\"field-sub\">ETD: {{etd}}</div>\n                        {{/if}}\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">OCEAN VESSEL / VOYAGE</div>\n                        <div class=\"field-value\">{{vessel_name}} / {{voyage_no}}</div>\n                        {{#if imo_number}}\n                        <div class=\"field-sub\">IMO: {{imo_number}} / Flag: {{vessel_flag}}</div>\n                        {{/if}}\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">PORT OF DISCHARGE (POD)</div>\n                        <div class=\"field-value\">{{pod}}</div>\n                        {{#if eta}}\n                        <div class=\"field-sub\">ETA: {{eta}}</div>\n                        {{/if}}\n                    </div>\n                </div>\n                <!-- Place of receipt / Pre carriage / Delivery -->\n                <div class=\"four-col-grid\">\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">PLACE OF RECEIPT</div>\n                        <div class=\"field-value\">{{place_receipt}}</div>\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">PRE-CARRIAGE BY</div>\n                        <div class=\"field-value\">{{pre_carriage}}</div>\n                        <div class=\"field-sub\">Container# {{container_no}}</div>\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">PLACE OF DELIVERY</div>\n                        <div class=\"field-value\">{{place_of_delivery}}</div>\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">FREIGHT PAYABLE AT</div>\n                        <div class=\"field-value\">{{freight_payable_at}}</div>\n                    </div>\n                </div>\n                <!-- Container & Seal numbers + marks -->\n                <div class=\"two-col-grid\">\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">CONTAINER / SEAL NUMBERS</div>\n                        {{#containers}}\n                        <div class=\"field-value\" style=\"margin-bottom: 6px;\">{{container_no}} ({{container_type}}) · Seal: {{seal_no}}</div>\n                        <div class=\"field-sub\" style=\"margin-top: 0; margin-bottom: 6px;\">{{origin}} → {{destination}}</div>\n                        {{/containers}}\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">MARKS & NUMBERS</div>\n                        <div class=\"field-value\">{{marks_numbers}}</div>\n                        <div class=\"field-sub\">{{package_type}} labelled accordingly</div>\n                    </div>\n                </div>\n                <!-- CARGO DETAILS - container-wise cargo table -->\n                <div class=\"cargo-section\">\n                    <div class=\"section-title\">✦ PARTICULARS FURNISHED BY SHIPPER – CARGO DESCRIPTION</div>\n                    <table class=\"cargo-table\">\n                        <thead>\n                            <tr>\n                                <th>Container</th>\n                                <th>Marks & Nos.</th>\n                                <th>No. of Packages</th>\n                                <th>Description of Goods (HS Code)</th>\n                                <th>Commodity</th>\n                                <th>Gross Weight (kg)</th>\n                                <th>Measurement (CBM)</th>\n                            </tr>\n                        </thead>\n                        <tbody>\n                            <!-- Pre-rendered cargo table HTML from manifest_v2.php -->\n                            {{cargo_table_html}}\n                        </tbody>\n                    </table>\n                    <div class=\"small-note\" style=\"margin-top: 8px; padding-left: 2px;\">Shipper\'s declared weight & measurement. Carrier not responsible for unknown particulars.</div>\n                </div>\n                <!-- additional shipping terms / service mode -->\n                <div class=\"three-col-grid\" style=\"margin-top: 0.2rem;\">\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">TRANSPORT MODE</div>\n                        <div class=\"field-value\">{{type_of_shipment}}</div>\n                        <div class=\"field-sub\">{{mode}}</div>\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">ORIGINALS RELEASED AT</div>\n                        <div class=\"field-value\">{{originals_release_at}} ({{no_of_original_bl}}/{{num_originals}} Originals)</div>\n                    </div>\n                    <div class=\"field-group\">\n                        <div class=\"field-label\">TEMPERATURE / REEFER</div>\n                        <div class=\"field-value\">{{temperature_requirement}}</div>\n                    </div>\n                </div>\n                <!-- charges & declaration block classic style -->\n                <div class=\"field-group\" style=\"margin: 1rem 0 0.5rem 0; background: #fefcf5; border-color: #e2d9c3;\">\n                    <div class=\"field-label\">FREIGHT & CHARGES SUMMARY</div>\n                    <div class=\"field-value\" style=\"font-size: 0.8rem;\">\n                        <span>Ocean Freight: {{currency}} {{ocean_freight}} | THC: {{currency}} {{local_charges}} | Documentation fee: {{currency}} {{doc_fee}}</span>\n                    </div>\n                    <div class=\"field-sub\">{{destination_charges}}</div>\n                </div>\n                <div class=\"divider-light\"></div>\n                <!-- Footer: Terms and signature area (Standard Bill of Lading format) -->\n                <div class=\"footer-grid\">\n                    <div class=\"terms-text\">\n                        <strong style=\"font-size: 0.75rem;\">RECEIVED for shipment</strong>\n                        in apparent good order and condition unless otherwise noted. The goods to be delivered at the port of discharge or place of delivery mentioned above, subject to all terms & conditions of the carrier\'s Bill of Lading (including liberties, exceptions, and limitation of liability).\n                        <br>\n                        <br>\n                        IN WITNESS WHEREOF, {{no_of_original_bl}} ({{num_originals}}) original Bills of Lading have been signed, all of this tenor and date, one of which being accomplished the others to stand void.\n                        <br>\n                        <span style=\"font-size: 0.65rem;\">{{declaration}}</span>\n                    </div>\n                    <div class=\"sign-block\">\n                        <div>\n                            <span class=\"field-label\">PLACE & DATE OF ISSUE</span>\n                        </div>\n                        <div class=\"field-value\" style=\"font-size: 0.85rem;\">{{place_of_issue}} – {{date_of_issue}}</div>\n                        {{#if etd}}\n                        <div style=\"margin-top: 6px;\">\n                            <span class=\"field-label\">ON BOARD DATE</span>\n                        </div>\n                        <div class=\"field-value\" style=\"font-size: 0.85rem;\">{{etd}}</div>\n                        {{/if}}\n                        <div style=\"margin-top: 12px;\">\n                            <span class=\"field-label\">CARRIER\'S SIGNATURE</span>\n                        </div>\n                        <div class=\"field-value\">{{carrier_name}}</div>\n                        {{#if signatory}}\n                        <div class=\"field-sub\">{{signatory}} ({{designation}})</div>\n                        {{/if}}\n                        <div class=\"sign-line\">\n                            <span>As Carrier (Signed by) _______________________</span>\n                            <span>As Agent (Digitally verified)</span>\n                        </div>\n                        <div class=\"small-note\" style=\"margin-top: 8px;\">For and on behalf of Master / Carrier</div>\n                    </div>\n                </div>\n                <!-- additional field: number of originals -->\n                <div class=\"two-col-grid\" style=\"margin-top: 1rem; border-top: 1px dashed #e2e8f0; padding-top: 1rem;\">\n                    <div class=\"field-group\" style=\"background: #fafcff;\">\n                        <div class=\"field-label\">NUMBER OF ORIGINAL B/Ls</div>\n                        <div class=\"field-value\">{{no_of_original_bl}} ({{num_originals}}) originals issued, non-negotiable unless endorsed</div>\n                    </div>\n                    <div class=\"field-group\" style=\"background: #fafcff;\">\n                        <div class=\"field-label\">SHIPPING TERMS / INCOTERMS</div>\n                        <div class=\"field-value\">{{incoterms}} – {{pod}} ({{incoterms_version}})</div>\n                    </div>\n                </div>\n            </div>\n            <!-- end bol-inner -->\n            <div class=\"print-btn\">\n                <button class=\"btn-print\" onclick=\"window.print();\">🖨️ PRINT BILL OF LADING (PDF)</button>\n            </div>\n        </div>\n        <!-- end bol-document -->\n    </body>\n</html>', 0, 1, '2026-06-18 11:07:16', '2026-08-25 14:02:41'),
(9, 'bill_of_lading', 'Bill of Lading v3', '<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Bill of Lading Preview</title>\n    <style>\n        * { box-sizing: border-box; }\n        body {\n            margin: 0;\n            background: #eef2f5;\n            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;\n            padding: 30px 20px;\n            color: #111;\n        }\n        .bol-container {\n            max-width: 1260px;\n            margin: 0 auto;\n            background: #fff;\n            border: 1px solid #e2e8f0;\n            box-shadow: 0 20px 35px -12px rgba(0,0,0,.12);\n        }\n        .bol-header {\n            display: flex;\n            justify-content: space-between;\n            align-items: center;\n            padding: 26px 32px 22px;\n            background: #1a365d;\n            border-bottom: 3px solid #2b6cb0;\n            color: #fff;\n        }\n        .corporate-logo {\n            background: #fff;\n            color: #1a365d;\n            display: inline-block;\n            padding: 8px 18px;\n            border-left: 3px solid #2b6cb0;\n            font-size: 20px;\n            font-weight: 700;\n            letter-spacing: 1px;\n        }\n        .corporate-logo span {\n            display: block;\n            color: #2c5a74;\n            font-size: 11px;\n            font-weight: 500;\n            letter-spacing: .3px;\n            margin-top: 4px;\n        }\n        .logo-website {\n            color: #2b6cb0;\n            font-size: 10px;\n            letter-spacing: .6px;\n            margin-top: 6px;\n            text-transform: uppercase;\n        }\n        .title-section { text-align: right; }\n        .title-section h1 {\n            font-size: 34px;\n            letter-spacing: 5px;\n            margin: 0 0 5px;\n            color: #2b6cb0;\n        }\n        .title-section p {\n            font-size: 10px;\n            color: #cbdbe0;\n            letter-spacing: 1px;\n            margin: 0;\n        }\n        .doc-badge {\n            display: inline-block;\n            margin-top: 10px;\n            background: #2b6cb0;\n            color: #fff;\n            padding: 4px 14px;\n            border-radius: 30px;\n            font-size: 8px;\n            font-weight: 800;\n            letter-spacing: 1px;\n        }\n        .grid-2col {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 1px solid #dee2e6;\n        }\n        .cell {\n            padding: 14px 20px;\n            border-right: 1px solid #dee2e6;\n        }\n        .cell:last-child { border-right: none; }\n        .inner-split {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n        }\n        .sub-cell {\n            padding: 8px 12px;\n        }\n        .sub-cell:first-child {\n            border-right: 1px solid #dee2e6;\n        }\n        .cell-label {\n            font-size: 9px;\n            font-weight: 800;\n            text-transform: uppercase;\n            letter-spacing: 1.2px;\n            color: #2c5a74;\n            margin-bottom: 6px;\n            border-left: 2px solid #2b6cb0;\n            padding-left: 7px;\n        }\n        .cell-value {\n            font-size: 12px;\n            font-weight: 700;\n        }\n        .cell-sub {\n            font-size: 9.5px;\n            color: #2d3e50;\n            margin-top: 3px;\n        }\n        .ref-number {\n            font-size: 13px;\n            font-weight: 800;\n            color: #1a365d;\n        }\n        .full-width-cell {\n            padding: 14px 22px;\n            border-bottom: 1px solid #dee2e6;\n        }\n        .grid-4col {\n            display: grid;\n            grid-template-columns: 1fr 1.2fr 0.9fr 1fr;\n            border-bottom: 1px solid #dee2e6;\n        }\n        .quad-cell {\n            padding: 12px 16px;\n            border-right: 1px solid #dee2e6;\n        }\n        .quad-cell:last-child { border-right: none; }\n        .cargo-section-title {\n            background: #1a365d;\n            padding: 10px 22px;\n            font-size: 11px;\n            font-weight: 800;\n            text-transform: uppercase;\n            letter-spacing: 2px;\n            color: #fff;\n            border-bottom: 1px solid #dce5ec;\n            border-top: 1px solid #dce5ec;\n        }\n        .cargo-table-wrap {\n            padding: 0 0 0 0;\n            overflow-x: auto;\n        }\n        .cargo-table {\n            width: 100%;\n            border-collapse: collapse;\n            font-size: 9.5px;\n        }\n        .cargo-table th {\n            background: #f8fafc;\n            padding: 10px 12px;\n            text-align: left;\n            font-weight: 800;\n            color: #1e4a6b;\n            border-bottom: 2px solid #2b6cb0;\n            font-size: 8.5px;\n            text-transform: uppercase;\n            letter-spacing: 0.8px;\n        }\n        .cargo-table td {\n            padding: 10px 12px;\n            border-bottom: 1px solid #e9edf2;\n            vertical-align: top;\n        }\n        .cargo-table tr:last-child td {\n            border-bottom: none;\n        }\n        .cargo-table .total-row {\n            background: #f9f5e8;\n            font-weight: 700;\n        }\n        .cargo-table .grand-total {\n            background: #1a365d;\n            color: #fff;\n            font-weight: 800;\n        }\n        .cargo-table .grand-total td {\n            color: #fff;\n        }\n        .cargo-table .container-header {\n            background: #f6f9fc;\n            font-weight: 700;\n        }\n        .cargo-table .container-header td {\n            padding: 8px 12px;\n            font-size: 10px;\n        }\n        .goods-desc-small {\n            font-size: 9px;\n            font-weight: 500;\n        }\n        .hs-code-text {\n            font-size: 8px;\n            color: #3a6b92;\n            margin-top: 2px;\n        }\n        .marks-text {\n            font-weight: 600;\n            font-size: 9px;\n            line-height: 1.45;\n        }\n        .container-code {\n            font-weight: 700;\n            font-size: 10px;\n            color: #1a365d;\n        }\n        .hs-code {\n            display: inline-block;\n            background: #f5f1e6;\n            padding: 2px 6px;\n            border-radius: 12px;\n            margin-top: 4px;\n            font-size: 8.5px;\n        }\n        .freight-note {\n            font-size: 9px;\n            font-weight: 700;\n            color: #b97f10;\n            margin-top: 4px;\n        }\n        .items-wrapper {\n            padding: 16px 24px;\n            background: #fff;\n            border-top: 1px solid #e2e8f0;\n        }\n        .items-title {\n            font-size: 11px;\n            font-weight: 800;\n            text-transform: uppercase;\n            letter-spacing: 2px;\n            color: #1a365d;\n            margin-bottom: 12px;\n            border-left: 3px solid #2b6cb0;\n            padding-left: 12px;\n        }\n        .item-table {\n            width: 100%;\n            border-collapse: collapse;\n            font-size: 8.5px;\n        }\n        .item-table th {\n            background: #eef3fa;\n            padding: 9px 8px;\n            text-align: left;\n            font-weight: 800;\n            color: #1a365d;\n            border: 1px solid #cfdfed;\n        }\n        .item-table td {\n            border: 1px solid #e2e8f0;\n            padding: 8px;\n        }\n        .item-table .total-row {\n            background: #f9f5e8;\n            font-weight: 800;\n        }\n        .footer-two {\n            display: grid;\n            grid-template-columns: 2.2fr 1fr;\n            border-top: 2px solid #1a365d;\n        }\n        .terms-box {\n            padding: 20px 24px;\n            font-size: 7.5px;\n            line-height: 1.6;\n            text-align: justify;\n            border-right: 1px solid #e2e8f0;\n        }\n        .sign-box {\n            padding: 20px 20px;\n        }\n        .sign-place {\n            font-size: 10px;\n            font-weight: 600;\n            margin-bottom: 20px;\n        }\n        .carrier-name-footer {\n            font-weight: 800;\n            font-size: 10px;\n            color: #1a365d;\n            margin: 12px 0;\n        }\n        .sign-lines {\n            display: flex;\n            justify-content: space-between;\n            margin-top: 25px;\n            font-size: 7px;\n            font-weight: 800;\n            text-transform: uppercase;\n            color: #3b6a88;\n            border-top: 1px dashed #cbd5e1;\n            padding-top: 14px;\n        }\n        .action-bar {\n            background: #f8fafc;\n            padding: 14px;\n            text-align: center;\n            border-top: 1px solid #dee2e6;\n        }\n        .btn-print {\n            background: #1a365d;\n            color: white;\n            border: none;\n            padding: 8px 32px;\n            font-weight: 700;\n            border-radius: 40px;\n            cursor: pointer;\n        }\n        .btn-print:hover {\n            background: #2b6cb0;\n        }\n        .small-meta {\n            font-size: 7px;\n            color: #4b6a7c;\n            margin-top: 3px;\n        }\n\n        @media print {\n            body { background: white; padding: 0; margin: 0; }\n            .bol-container { box-shadow: none; border: 1px solid #ccc; }\n            .btn-print, .action-bar { display: none; }\n            .bol-header { background: #1a365d !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }\n            .cargo-table th { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }\n            .cargo-section-title { background: #1a365d !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }\n            .cargo-table .grand-total { background: #1a365d !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }\n        }\n\n        @media (max-width: 768px) {\n            .grid-2col, .grid-4col, .footer-two { grid-template-columns: 1fr; }\n            .cell { border-right: none; }\n            .quad-cell { border-right: none; }\n            .bol-header { flex-direction: column; text-align: center; padding: 20px; }\n            .title-section { text-align: center; margin-top: 15px; }\n            .cargo-table { font-size: 8px; }\n            .cargo-table th, .cargo-table td { padding: 6px 8px; }\n        }\n    </style>\n</head>\n<body>\n<div class=\"bol-container\">\n    <div class=\"bol-header\">\n        <div>\n            <div class=\"corporate-logo\">\n                {{company_display_name}}\n                <span>{{company_tagline}}</span>\n            </div>\n            <div class=\"logo-website\">{{company_website}} | {{company_established}}</div>\n        </div>\n        <div class=\"title-section\">\n            <h1>BILL OF LADING</h1>\n            <p>{{bl_type}}</p>\n            <div class=\"doc-badge\">{{incoterms}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Shipper / Exporter</div>\n            <div class=\"cell-value\">{{shipper_name}}</div>\n            <div class=\"cell-sub\">{{shipper_address}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"inner-split\">\n                <div class=\"sub-cell\">\n                    <div class=\"cell-label\">B/L No.</div>\n                    <div class=\"ref-number\">{{tracking_number}}</div>\n                </div>\n                <div class=\"sub-cell\">\n                    <div class=\"cell-label\">Booking No.</div>\n                    <div class=\"cell-value\">{{booking_no}}</div>\n                </div>\n            </div>\n            <div class=\"sub-cell\" style=\"padding: 8px 12px 0; border-top: 1px solid #dee2e6; margin-top: 8px;\">\n                <div class=\"cell-label\">Export Reference</div>\n                <div class=\"cell-sub\">{{export_reference}}</div>\n            </div>\n        </div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Consignee</div>\n            <div class=\"cell-value\">{{consignee_name}}</div>\n            <div class=\"cell-sub\">{{consignee_address}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"cell-label\">Forwarding Agent / NVOCC</div>\n            <div class=\"cell-value\">{{forwarding_agent}}</div>\n            <div class=\"cell-sub\">{{forwarding_agent_address}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Notify Party</div>\n            <div class=\"cell-value\">{{notify_name}}</div>\n            <div class=\"cell-sub\">{{notify_address}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"cell-label\">Place of Receipt / Origin</div>\n            <div class=\"cell-value\">{{place_receipt}}</div>\n        </div>\n    </div>\n\n    <div class=\"full-width-cell\">\n        <div class=\"cell-label\">Destination Agent / Correspondent</div>\n        <div class=\"cell-value\">{{destination_agent}}</div>\n        <div class=\"cell-sub\">{{destination_agent_address}} | Tel: {{destination_agent_phone}} | Email: {{destination_agent_email}}</div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Port of Loading (POL)</div>\n            <div class=\"cell-value\">{{pol}}</div>\n            <div class=\"cell-sub\">ETD: {{etd}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"cell-label\">Pre-carriage by</div>\n            <div class=\"cell-sub\">{{pre_carriage}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-4col\">\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Ocean Vessel / Voyage</div>\n            <div class=\"cell-value\">{{vessel_name}} / {{voyage_no}}</div>\n            <div class=\"small-meta\">IMO: {{imo_number}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Port of Discharge (POD)</div>\n            <div class=\"cell-value\">{{pod}}</div>\n            <div class=\"small-meta\">ETA: {{eta}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Place of Delivery</div>\n            <div class=\"cell-value\">{{place_of_delivery}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Freight Payable at</div>\n            <div class=\"cell-value\">{{freight_payable_at}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-4col\">\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Mode of Movement</div>\n            <div class=\"cell-value\">{{type_of_shipment}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Export Carrier</div>\n            <div class=\"cell-value\">{{export_carrier}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Originals Issued at</div>\n            <div class=\"cell-value\">{{originals_release_at}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Terms of Delivery</div>\n            <div class=\"cell-value\">{{incoterms}}</div>\n        </div>\n    </div>\n\n    <div class=\"cargo-section-title\">▸ PARTICULARS DECLARED BY SHIPPER – CARGO DESCRIPTION</div>\n\n    <!-- Container-wise Cargo Table -->\n    <div class=\"cargo-table-wrap\">\n        <table class=\"cargo-table\">\n            <thead>\n                <tr>\n                    <th style=\"width:12%;\">Container</th>\n                    <th style=\"width:12%;\">Marks &amp; Nos.</th>\n                    <th style=\"width:12%;\">No. of Packages</th>\n                    <th style=\"width:28%;\">Description of Goods (HS Code)</th>\n                    <th style=\"width:12%;\">Commodity</th>\n                    <th style=\"width:12%;\">Gross Weight (kg)</th>\n                    <th style=\"width:12%;\">Measurement (CBM)</th>\n                </tr>\n            </thead>\n            <tbody>\n                {{cargo_table_html}}\n            </tbody>\n        </table>\n    </div>\n\n    <div class=\"items-wrapper\">\n        <div class=\"items-title\">PACKAGE ITEMIZATION BREAKDOWN</div>\n        <table class=\"item-table\">\n            <thead>\n                <tr>\n                    <th>Commodity / Article</th>\n                    <th>Cartons / Pkgs</th>\n                    <th>Weight (kg)</th>\n                    <th>Measurement (CBM)</th>\n                    <th>Unit Value ({{currency}})</th>\n                </tr>\n            </thead>\n            <tbody>\n                {{#items}}\n                <tr>\n                    <td>{{item_description}}</td>\n                    <td>{{quantity}}</td>\n                    <td>{{weight}}</td>\n                    <td>{{cbm}}</td>\n                    <td>{{currency}} {{value}}</td>\n                </tr>\n                {{/items}}\n            </tbody>\n            <tfoot>\n                <tr class=\"total-row\">\n                    <td><strong>TOTAL SHIPMENT</strong></td>\n                    <td><strong>{{total_packages}}</strong></td>\n                    <td><strong>{{total_weight}} KGS</strong></td>\n                    <td><strong>{{total_cbm}} CBM</strong></td>\n                    <td><strong>{{currency}} {{total_value}}</strong></td>\n                </tr>\n            </tfoot>\n        </table>\n    </div>\n\n    <div class=\"full-width-cell\" style=\"border-top: 1px solid #e2e8f0; background: #fff;\">\n        <div class=\"cell-label\">Special Instructions / Hazardous Declaration</div>\n        <div class=\"cell-sub\">{{special_instructions}}</div>\n    </div>\n\n    <div class=\"footer-two\">\n        <div class=\"terms-box\">\n            <strong>RECEIVED for shipment</strong> in apparent good order and condition, except as otherwise noted. The total number of packages or units stated herein. This Bill of Lading is issued subject to the Carrier\'s standard terms and conditions. The merchant agrees that this shipment is subject to the Hague-Visby Rules where applicable. In witness whereof, {{no_of_original_bl}} original Bills of Lading have been signed, one of which being accomplished, the others to stand void.\n        </div>\n        <div class=\"sign-box\">\n            <div class=\"sign-place\">PLACE &amp; DATE OF ISSUANCE: <strong>{{place_of_issue}} – {{date_of_issue}}</strong></div>\n            <div class=\"sign-place\">ON BOARD DATE: <strong>{{etd}}</strong></div>\n            <div class=\"carrier-name-footer\">{{carrier_name}}<br>As Carrier</div>\n            <div class=\"small-meta\">Signatory: {{signatory}} ({{designation}})</div>\n            <div class=\"sign-lines\">\n                <span>Authorized Signature</span>\n                <span>Electronically Verified</span>\n            </div>\n            <div class=\"small-meta\" style=\"margin-top:10px;\">{{bl_type}} – Issued at {{place_of_issue}}</div>\n        </div>\n    </div>\n\n    <div class=\"action-bar\">\n        <button class=\"btn-print\" onclick=\"window.print();\">PRINT / EXPORT PDF (Official B/L)</button>\n    </div>\n</div>\n</body>\n</html>', 0, 1, '2026-06-18 11:35:37', '2026-08-25 14:11:41');
INSERT INTO `document_templates` (`id`, `document_type`, `template_name`, `html_template`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(10, 'bill_of_lading', 'Bill of Lading V2', '<!DOCTYPE html>\n<html lang=\"en\">\n\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Combined Transport Bill of Lading</title>\n    <style>\n        * {\n            box-sizing: border-box;\n        }\n\n        body {\n            margin: 0;\n            background: #eef2f5;\n            font-family: Arial, Helvetica, sans-serif;\n            padding: 30px 20px;\n            color: #000;\n        }\n\n        .bol-container {\n            max-width: 1100px;\n            margin: 0 auto;\n            background: #fff;\n            border: 2px solid #000;\n        }\n\n        .bol-title {\n            text-align: center;\n            font-size: 20px;\n            font-weight: 800;\n            letter-spacing: 1px;\n            padding: 14px 10px 10px;\n            border-bottom: 2px solid #000;\n        }\n\n        .top-grid {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n        }\n\n        .top-left,\n        .top-right {\n            border-right: 2px solid #000;\n        }\n\n        .top-right {\n            border-right: none;\n        }\n\n        .box {\n            border-bottom: 1px solid #000;\n            padding: 8px 12px;\n            min-height: 70px;\n        }\n\n        .box-label {\n            font-size: 10px;\n            font-weight: 700;\n            text-transform: uppercase;\n            margin-bottom: 4px;\n        }\n\n        .box-value {\n            font-size: 11.5px;\n            font-weight: 700;\n            line-height: 1.5;\n            white-space: pre-line;\n        }\n\n        .logo-box {\n            display: flex;\n            align-items: center;\n            justify-content: center;\n            min-height: 70px;\n            border-bottom: 1px solid #000;\n            padding: 10px;\n        }\n\n        .logo-box img {\n            max-height: 55px;\n        }\n\n        .logo-text {\n            font-size: 26px;\n            font-weight: 900;\n            letter-spacing: 2px;\n        }\n\n        .logo-sub {\n            font-size: 11px;\n            font-weight: 600;\n            letter-spacing: 3px;\n            text-align: center;\n        }\n\n        .right-two-col {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 1px solid #000;\n        }\n\n        .right-two-col .box {\n            border-bottom: none;\n            border-right: 1px solid #000;\n        }\n\n        .right-two-col .box:last-child {\n            border-right: none;\n        }\n\n        .bl-no {\n            font-size: 12px;\n            font-weight: 800;\n        }\n\n        .right-three-col {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 1px solid #000;\n        }\n\n        .right-three-col .box {\n            border-bottom: none;\n            border-right: 1px solid #000;\n        }\n\n        .right-three-col .box:last-child {\n            border-right: none;\n        }\n\n        .full-box {\n            border-bottom: 1px solid #000;\n            padding: 8px 12px;\n            min-height: 50px;\n        }\n\n        .mid-grid {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 2px solid #000;\n        }\n\n        .mid-grid .box {\n            border-right: 2px solid #000;\n        }\n\n        .mid-grid .box:last-child {\n            border-right: none;\n        }\n\n        .cargo-header {\n            text-align: center;\n            font-size: 11px;\n            font-weight: 800;\n            text-transform: uppercase;\n            padding: 6px;\n            border-bottom: 1px solid #000;\n            background: #f1f1f1;\n        }\n\n        .cargo-table-wrap {\n            display: flex;\n        }\n\n        .cargo-col-1 {\n            width: 14%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 10px;\n            font-weight: 700;\n        }\n\n        .cargo-col-2 {\n            width: 10%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 11px;\n            font-weight: 700;\n            text-align: center;\n        }\n\n        .cargo-col-3 {\n            width: 50%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 10.5px;\n            line-height: 1.6;\n        }\n\n        .cargo-col-4 {\n            width: 13%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 10.5px;\n            font-weight: 700;\n        }\n\n        .cargo-col-5 {\n            width: 13%;\n            padding: 8px 10px;\n            font-size: 10.5px;\n            font-weight: 700;\n        }\n\n        .cargo-table-wrap {\n            border-bottom: 2px solid #000;\n            min-height: 260px;\n        }\n\n        .stamp {\n            display: inline-block;\n            border: 2px solid #b91c1c;\n            color: #b91c1c;\n            font-weight: 800;\n            font-size: 13px;\n            padding: 4px 10px;\n            transform: rotate(-12deg);\n            margin-top: 20px;\n            letter-spacing: 1px;\n        }\n\n        .bottom-grid {\n            display: grid;\n            grid-template-columns: 1.4fr 1fr;\n        }\n\n        .bottom-left {\n            border-right: 2px solid #000;\n        }\n\n        .bottom-left .box {\n            min-height: 40px;\n        }\n\n        .charges-table {\n            width: 100%;\n            border-collapse: collapse;\n            font-size: 9.5px;\n        }\n\n        .charges-table th,\n        .charges-table td {\n            border: 1px solid #000;\n            padding: 5px 6px;\n            text-align: left;\n        }\n\n        .jurisdiction {\n            padding: 10px 12px;\n            font-size: 8.5px;\n            line-height: 1.5;\n            text-align: center;\n            border-top: 1px solid #000;\n        }\n\n        .jurisdiction .cn {\n            font-size: 11px;\n            margin-top: 4px;\n        }\n\n        .freight-total {\n            display: grid;\n            grid-template-columns: 1fr 1fr 1fr;\n            border-top: 1px solid #000;\n        }\n\n        .freight-total .box {\n            border-right: 1px solid #000;\n            min-height: 35px;\n        }\n\n        .freight-total .box:last-child {\n            border-right: none;\n        }\n\n        .right-box {\n            border-bottom: 1px solid #000;\n            padding: 8px 12px;\n        }\n\n        .right-box.last {\n            border-bottom: none;\n        }\n\n        .signed-line {\n            font-size: 11px;\n            font-weight: 800;\n            margin-top: 6px;\n        }\n\n        .action-bar {\n            background: #f8fafc;\n            padding: 14px;\n            text-align: center;\n            border-top: 1px solid #dee2e6;\n        }\n\n        .btn-print {\n            background: #1a365d;\n            color: white;\n            border: none;\n            padding: 8px 32px;\n            font-weight: 700;\n            border-radius: 40px;\n            cursor: pointer;\n        }\n\n        .btn-print:hover {\n            background: #2b6cb0;\n        }\n\n        @media print {\n            body {\n                background: white;\n                padding: 0;\n                margin: 0;\n            }\n\n            .bol-container {\n                border: 2px solid #000;\n            }\n\n            .btn-print,\n            .action-bar {\n                display: none;\n            }\n        }\n    </style>\n</head>\n\n<body>\n    <div class=\"bol-container\">\n        <div class=\"bol-title\">COMBINED TRANSPORT BILL OF LADING</div>\n\n        <div class=\"top-grid\">\n            <div class=\"top-left\">\n                <div class=\"box\">\n                    <div class=\"box-label\">Shipper/Exporter (Complete Name and Address)</div>\n                    <div class=\"box-value\">{{shipper_name}}\n                        {{shipper_address}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Consignee (If \"To Order\" So Indicate)</div>\n                    <div class=\"box-value\">{{consignee_name}}\n                        {{consignee_address}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Notify Party (No Claim Shall Attach For Failure To Notify)</div>\n                    <div class=\"box-value\">{{notify_name}}\n                        {{notify_address}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Pre-Carriage By</div>\n                    <div class=\"box-value\">{{pre_carriage}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Ocean Vessel / Voy</div>\n                    <div class=\"box-value\">{{vessel_name}} / {{voyage_no}}</div>\n                </div>\n                <div class=\"box\" style=\"border-bottom:none;\">\n                    <div class=\"box-label\">Port of Discharge</div>\n                    <div class=\"box-value\">{{pod}}</div>\n                </div>\n            </div>\n\n            <div class=\"top-right\">\n                <div class=\"logo-box\">\n                    <div>\n                        <div class=\"logo-text\">{{logo}}</div>\n                        <div class=\"logo-text\">{{company_display_name}}</div>\n                        <div class=\"logo-sub\">{{company_tagline}}</div>\n                    </div>\n                </div>\n\n                <div class=\"right-two-col\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Booking No.</div>\n                        <div class=\"box-value\">{{booking_no}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Bill of Lading No.</div>\n                        <div class=\"bl-no\">{{tracking_number}}</div>\n                    </div>\n                </div>\n\n                <div class=\"right-two-col\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Export Reference</div>\n                        <div class=\"box-value\">{{export_reference}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Carrier\'s References</div>\n                        <div class=\"box-value\">{{carrier_reference}}</div>\n                    </div>\n                </div>\n\n                <div class=\"box\">\n                    <div class=\"box-label\">Forwarding Agent - References</div>\n                    <div class=\"box-value\">{{forwarding_agent}}</div>\n                </div>\n\n                <div class=\"box\">\n                    <div class=\"box-label\">For Delivery Please Contact</div>\n                    <div class=\"box-value\">{{destination_agent}}\n                        {{destination_agent_address}}\n                        Tel: {{destination_agent_phone}}\n                        Email: {{destination_agent_email}}</div>\n                </div>\n\n                <div class=\"right-three-col\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Place of Receipt</div>\n                        <div class=\"box-value\">{{place_receipt}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Port of Loading</div>\n                        <div class=\"box-value\">{{pol}}</div>\n                    </div>\n                </div>\n\n                <div class=\"right-two-col\" style=\"border-bottom:none;\">\n                    <div class=\"box\" style=\"border-bottom:none;\">\n                        <div class=\"box-label\">Port of Discharge</div>\n                        <div class=\"box-value\">{{pod}}</div>\n                    </div>\n                    <div class=\"box\" style=\"border-bottom:none;\">\n                        <div class=\"box-label\">Place of Delivery</div>\n                        <div class=\"box-value\">{{place_of_delivery}}</div>\n                    </div>\n                </div>\n            </div>\n        </div>\n\n        <div class=\"cargo-header\">Particular Declared by Shipper But Not Acknowledged by the Carrier</div>\n        <div class=\"cargo-table-wrap\">\n            <div class=\"cargo-col-1\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">CNTR. NOS W/SEAL NOS. MARKS &amp;\n                    NUMBERS</div>\n                {{container_marks_html}}\n            </div>\n            <div class=\"cargo-col-2\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">NO. OF PKGS. OR SHIPPING UNITS</div>\n                {{total_packages}}\n            </div>\n            <div class=\"cargo-col-3\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">DESCRIPTION OF GOODS &amp; PACKAGES\n                </div>\n                {{cargo_description_html}}\n            </div>\n            <div class=\"cargo-col-4\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">GROSS WEIGHT</div>\n                Gr. Wt.<br>{{gross_weight}} KGS<br><br>\n                Net Wt.<br>{{net_weight}} KGS\n            </div>\n            <div class=\"cargo-col-5\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">MEASUREMENT</div>\n                {{measurement}}\n                {{#if_surrendered}}\n                <div class=\"stamp\">OBL SURRENDERED</div>\n                {{/if_surrendered}}\n            </div>\n        </div>\n\n        <div class=\"bottom-grid\">\n            <div class=\"bottom-left\">\n                <div class=\"box\">\n                    <div class=\"box-label\">Type of Movement</div>\n                    <div class=\"box-value\">{{type_of_shipment}}</div>\n                </div>\n                <div style=\"display:grid; grid-template-columns:1fr 1fr;\">\n                    <div class=\"box\" style=\"border-right:1px solid #000;\">\n                        <div class=\"box-label\">Carrier\'s Receipt. Total No. of Containers or Packages Received by\n                            Carrier</div>\n                        <div class=\"box-value\">{{total_containers}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Number of Original Bill of Lading</div>\n                        <div class=\"box-value\">({{no_of_original_bl}})</div>\n                    </div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Freight and Charges (Indicate Whether Prepaid or Collect)</div>\n                    <table class=\"charges-table\">\n                        <tr>\n                            <th>Charges</th>\n                            <th>Rate</th>\n                            <th>Basis</th>\n                            <th>Prepaid/Collect</th>\n                            <th>Amount</th>\n                        </tr>\n                        {{charges_rows_html}}\n                    </table>\n                </div>\n                <div class=\"freight-total\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Total Freight Prepaid</div>\n                        <div class=\"box-value\">{{total_freight_prepaid}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Total Freight Collect</div>\n                        <div class=\"box-value\">{{total_freight_collect}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Total Freight</div>\n                        <div class=\"box-value\">{{total_freight}}</div>\n                    </div>\n                </div>\n                <div class=\"jurisdiction\">\n                    \"The contract by or contained in this bill of lading is governed by the law of {{governing_law}} and\n                    any claim or dispute arising hereunder or in connection herewith shall be determined by the Courts\n                    in {{jurisdiction_place}} and no other Courts\"\n                </div>\n            </div>\n\n            <div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Excess Value Declaration: Refer to Clause 6(4)(B) + (C) on Reverse Side</div>\n                </div>\n                <div class=\"right-box\" style=\"font-size:9px; line-height:1.5;\">\n                    RECEIVED by the Carrier the Goods as specified above in apparent good order and condition unless\n                    otherwise stated, to be transported to such place as agreed, authorised or permitted herein and\n                    subject to all the terms and conditions appearing on the front and reverse of this Bill of Lading to\n                    which the Merchant agrees by accepting this Bill of Lading, any local privileges and customs\n                    notwithstanding.\n                </div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Place and Date of Issue</div>\n                    <div class=\"box-value\">{{place_of_issue}} &nbsp; {{date_of_issue}}</div>\n                </div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Freight Payable at</div>\n                    <div class=\"box-value\">{{freight_payable_at}}</div>\n                </div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Shipped on Board Date (Local Time)</div>\n                    <div class=\"box-value\">{{etd}}</div>\n                </div>\n                <div class=\"right-box last\">\n                    <div class=\"box-label\">Signed on Behalf of the Carrier</div>\n                    <div class=\"signed-line\">{{carrier_name}}</div>\n                    <div class=\"box-value\">{{signatory_location}}</div>\n                    <div style=\"margin-top:30px; font-size:9px;\">by ........................................</div>\n                </div>\n            </div>\n        </div>\n\n        <div class=\"action-bar\">\n            <button class=\"btn-print\" onclick=\"window.print();\">PRINT / EXPORT PDF (Official B/L)</button>\n        </div>\n    </div>\n</body>\n\n</html>', 0, 1, '2026-10-03 10:03:53', '2026-10-03 10:03:53'),
(11, 'bill_of_lading', 'Bill of lading v4', '<!DOCTYPE html>\n<html lang=\"en\">\n\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Combined Transport Bill of Lading</title>\n    <style>\n        * {\n            box-sizing: border-box;\n        }\n\n        body {\n            margin: 0;\n            background: #eef2f5;\n            font-family: Arial, Helvetica, sans-serif;\n            padding: 30px 20px;\n            color: #000;\n        }\n\n        .bol-container {\n            max-width: 1100px;\n            margin: 0 auto;\n            background: #fff;\n            border: 2px solid #000;\n        }\n\n        .bol-title {\n            text-align: center;\n            font-size: 20px;\n            font-weight: 800;\n            letter-spacing: 1px;\n            padding: 14px 10px 10px;\n            border-bottom: 2px solid #000;\n        }\n\n        .top-grid {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n        }\n\n        .top-left,\n        .top-right {\n            border-right: 2px solid #000;\n        }\n\n        .top-right {\n            border-right: none;\n        }\n\n        .box {\n            border-bottom: 1px solid #000;\n            padding: 8px 12px;\n            min-height: 70px;\n        }\n\n        .box-label {\n            font-size: 10px;\n            font-weight: 700;\n            text-transform: uppercase;\n            margin-bottom: 4px;\n        }\n\n        .box-value {\n            font-size: 11.5px;\n            font-weight: 700;\n            line-height: 1.5;\n            white-space: pre-line;\n        }\n\n        .logo-box {\n            display: flex;\n            align-items: center;\n            justify-content: center;\n            min-height: 70px;\n            border-bottom: 1px solid #000;\n            padding: 10px;\n        }\n\n        .logo-box img {\n            max-height: 55px;\n        }\n\n        .logo-text {\n            font-size: 26px;\n            font-weight: 900;\n            letter-spacing: 2px;\n        }\n\n        .logo-sub {\n            font-size: 11px;\n            font-weight: 600;\n            letter-spacing: 3px;\n            text-align: center;\n        }\n\n        .right-two-col {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 1px solid #000;\n        }\n\n        .right-two-col .box {\n            border-bottom: none;\n            border-right: 1px solid #000;\n        }\n\n        .right-two-col .box:last-child {\n            border-right: none;\n        }\n\n        .bl-no {\n            font-size: 12px;\n            font-weight: 800;\n        }\n\n        .right-three-col {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 1px solid #000;\n        }\n\n        .right-three-col .box {\n            border-bottom: none;\n            border-right: 1px solid #000;\n        }\n\n        .right-three-col .box:last-child {\n            border-right: none;\n        }\n\n        .full-box {\n            border-bottom: 1px solid #000;\n            padding: 8px 12px;\n            min-height: 50px;\n        }\n\n        .mid-grid {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 2px solid #000;\n        }\n\n        .mid-grid .box {\n            border-right: 2px solid #000;\n        }\n\n        .mid-grid .box:last-child {\n            border-right: none;\n        }\n\n        .cargo-header {\n            text-align: center;\n            font-size: 11px;\n            font-weight: 800;\n            text-transform: uppercase;\n            padding: 6px;\n            border-bottom: 1px solid #000;\n            background: #f1f1f1;\n        }\n\n        .cargo-table-wrap {\n            display: flex;\n        }\n\n        .cargo-col-1 {\n            width: 14%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 10px;\n            font-weight: 700;\n        }\n\n        .cargo-col-2 {\n            width: 10%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 11px;\n            font-weight: 700;\n            text-align: center;\n        }\n\n        .cargo-col-3 {\n            width: 50%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 10.5px;\n            line-height: 1.6;\n        }\n\n        .cargo-col-4 {\n            width: 13%;\n            border-right: 1px solid #000;\n            padding: 8px 10px;\n            font-size: 10.5px;\n            font-weight: 700;\n        }\n\n        .cargo-col-5 {\n            width: 13%;\n            padding: 8px 10px;\n            font-size: 10.5px;\n            font-weight: 700;\n        }\n\n        .cargo-table-wrap {\n            border-bottom: 2px solid #000;\n            min-height: 260px;\n        }\n\n        .stamp {\n            display: inline-block;\n            border: 2px solid #b91c1c;\n            color: #b91c1c;\n            font-weight: 800;\n            font-size: 13px;\n            padding: 4px 10px;\n            transform: rotate(-12deg);\n            margin-top: 20px;\n            letter-spacing: 1px;\n        }\n\n        .bottom-grid {\n            display: grid;\n            grid-template-columns: 1.4fr 1fr;\n        }\n\n        .bottom-left {\n            border-right: 2px solid #000;\n        }\n\n        .bottom-left .box {\n            min-height: 40px;\n        }\n\n        .charges-table {\n            width: 100%;\n            border-collapse: collapse;\n            font-size: 9.5px;\n        }\n\n        .charges-table th,\n        .charges-table td {\n            border: 1px solid #000;\n            padding: 5px 6px;\n            text-align: left;\n        }\n\n        .jurisdiction {\n            padding: 10px 12px;\n            font-size: 8.5px;\n            line-height: 1.5;\n            text-align: center;\n            border-top: 1px solid #000;\n        }\n\n        .jurisdiction .cn {\n            font-size: 11px;\n            margin-top: 4px;\n        }\n\n        .freight-total {\n            display: grid;\n            grid-template-columns: 1fr 1fr 1fr;\n            border-top: 1px solid #000;\n        }\n\n        .freight-total .box {\n            border-right: 1px solid #000;\n            min-height: 35px;\n        }\n\n        .freight-total .box:last-child {\n            border-right: none;\n        }\n\n        .right-box {\n            border-bottom: 1px solid #000;\n            padding: 8px 12px;\n        }\n\n        .right-box.last {\n            border-bottom: none;\n        }\n\n        .signed-line {\n            font-size: 11px;\n            font-weight: 800;\n            margin-top: 6px;\n        }\n\n        .action-bar {\n            background: #f8fafc;\n            padding: 14px;\n            text-align: center;\n            border-top: 1px solid #dee2e6;\n        }\n\n        .btn-print {\n            background: #1a365d;\n            color: white;\n            border: none;\n            padding: 8px 32px;\n            font-weight: 700;\n            border-radius: 40px;\n            cursor: pointer;\n        }\n\n        .btn-print:hover {\n            background: #2b6cb0;\n        }\n\n        @media print {\n            body {\n                background: white;\n                padding: 0;\n                margin: 0;\n            }\n\n            .bol-container {\n                border: 2px solid #000;\n            }\n\n            .btn-print,\n            .action-bar {\n                display: none;\n            }\n        }\n    </style>\n</head>\n\n<body>\n    <div class=\"bol-container\">\n        <div class=\"bol-title\">COMBINED TRANSPORT BILL OF LADING</div>\n\n        <div class=\"top-grid\">\n            <div class=\"top-left\">\n                <div class=\"box\">\n                    <div class=\"box-label\">Shipper/Exporter (Complete Name and Address)</div>\n                    <div class=\"box-value\">{{shipper_name}}\n                        {{shipper_address}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Consignee (If \"To Order\" So Indicate)</div>\n                    <div class=\"box-value\">{{consignee_name}}\n                        {{consignee_address}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Notify Party (No Claim Shall Attach For Failure To Notify)</div>\n                    <div class=\"box-value\">{{notify_name}}\n                        {{notify_address}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Pre-Carriage By</div>\n                    <div class=\"box-value\">{{pre_carriage}}</div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Ocean Vessel / Voy</div>\n                    <div class=\"box-value\">{{vessel_name}} / {{voyage_no}}</div>\n                </div>\n                <div class=\"box\" style=\"border-bottom:none;\">\n                    <div class=\"box-label\">Port of Discharge</div>\n                    <div class=\"box-value\">{{pod}}</div>\n                </div>\n            </div>\n\n            <div class=\"top-right\">\n                <div class=\"logo-box\">\n                    <div class=\"corporate-logo\">\n                        {{#if logo_path}}\n                        <img src=\"{{logo_path}}\" alt=\"{{company_display_name}}\" style=\"max-height:45px; display:block;\">\n                        {{/if}}\n                        {{#unless logo_path}}\n                        {{company_display_name}}\n                        {{/unless}}\n                        <span>{{company_tagline}}</span>\n                    </div>\n                </div>\n\n                <div class=\"right-two-col\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Booking No.</div>\n                        <div class=\"box-value\">{{booking_no}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Bill of Lading No.</div>\n                        <div class=\"bl-no\">{{tracking_number}}</div>\n                    </div>\n                </div>\n\n                <div class=\"right-two-col\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Export Reference</div>\n                        <div class=\"box-value\">{{export_reference}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Carrier\'s References</div>\n                        <div class=\"box-value\">{{carrier_reference}}</div>\n                    </div>\n                </div>\n\n                <div class=\"box\">\n                    <div class=\"box-label\">Forwarding Agent - References</div>\n                    <div class=\"box-value\">{{forwarding_agent}}</div>\n                </div>\n\n                <div class=\"box\">\n                    <div class=\"box-label\">For Delivery Please Contact</div>\n                    <div class=\"box-value\">{{destination_agent}}\n                        {{destination_agent_address}}\n                        Tel: {{destination_agent_phone}}\n                        Email: {{destination_agent_email}}</div>\n                </div>\n\n                <div class=\"right-three-col\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Place of Receipt</div>\n                        <div class=\"box-value\">{{place_receipt}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Port of Loading</div>\n                        <div class=\"box-value\">{{pol}}</div>\n                    </div>\n                </div>\n\n                <div class=\"right-two-col\" style=\"border-bottom:none;\">\n                    <div class=\"box\" style=\"border-bottom:none;\">\n                        <div class=\"box-label\">Port of Discharge</div>\n                        <div class=\"box-value\">{{pod}}</div>\n                    </div>\n                    <div class=\"box\" style=\"border-bottom:none;\">\n                        <div class=\"box-label\">Place of Delivery</div>\n                        <div class=\"box-value\">{{place_of_delivery}}</div>\n                    </div>\n                </div>\n            </div>\n        </div>\n\n        <div class=\"cargo-header\">Particular Declared by Shipper But Not Acknowledged by the Carrier</div>\n        <div class=\"cargo-table-wrap\">\n            <div class=\"cargo-col-1\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">CNTR. NOS W/SEAL NOS. MARKS &amp;\n                    NUMBERS</div>\n                {{container_marks_html}}\n            </div>\n            <div class=\"cargo-col-2\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">NO. OF PKGS. OR SHIPPING UNITS</div>\n                {{total_packages}}\n            </div>\n            <div class=\"cargo-col-3\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">DESCRIPTION OF GOODS &amp; PACKAGES\n                </div>\n                {{cargo_description_html}}\n            </div>\n            <div class=\"cargo-col-4\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">GROSS WEIGHT</div>\n                Gr. Wt.<br>{{gross_weight}} KGS<br><br>\n                Net Wt.<br>{{net_weight}} KGS\n            </div>\n            <div class=\"cargo-col-5\">\n                <div style=\"font-size:9px; font-weight:800; margin-bottom:6px;\">MEASUREMENT</div>\n                {{measurement}}\n                {{#if_surrendered}}\n                <div class=\"stamp\">OBL SURRENDERED</div>\n                {{/if_surrendered}}\n            </div>\n        </div>\n\n        <div class=\"bottom-grid\">\n            <div class=\"bottom-left\">\n                <div class=\"box\">\n                    <div class=\"box-label\">Type of Movement</div>\n                    <div class=\"box-value\">{{type_of_shipment}}</div>\n                </div>\n                <div style=\"display:grid; grid-template-columns:1fr 1fr;\">\n                    <div class=\"box\" style=\"border-right:1px solid #000;\">\n                        <div class=\"box-label\">Carrier\'s Receipt. Total No. of Containers or Packages Received by\n                            Carrier</div>\n                        <div class=\"box-value\">{{total_containers}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Number of Original Bill of Lading</div>\n                        <div class=\"box-value\">({{no_of_original_bl}})</div>\n                    </div>\n                </div>\n                <div class=\"box\">\n                    <div class=\"box-label\">Freight and Charges (Indicate Whether Prepaid or Collect)</div>\n                    <table class=\"charges-table\">\n                        <tr>\n                            <th>Charges</th>\n                            <th>Rate</th>\n                            <th>Basis</th>\n                            <th>Prepaid/Collect</th>\n                            <th>Amount</th>\n                        </tr>\n                        {{charges_rows_html}}\n                    </table>\n                </div>\n                <div class=\"freight-total\">\n                    <div class=\"box\">\n                        <div class=\"box-label\">Total Freight Prepaid</div>\n                        <div class=\"box-value\">{{total_freight_prepaid}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Total Freight Collect</div>\n                        <div class=\"box-value\">{{total_freight_collect}}</div>\n                    </div>\n                    <div class=\"box\">\n                        <div class=\"box-label\">Total Freight</div>\n                        <div class=\"box-value\">{{total_freight}}</div>\n                    </div>\n                </div>\n                <div class=\"jurisdiction\">\n                    \"The contract by or contained in this bill of lading is governed by the law of {{governing_law}} and\n                    any claim or dispute arising hereunder or in connection herewith shall be determined by the Courts\n                    in {{jurisdiction_place}} and no other Courts\"\n                </div>\n            </div>\n\n            <div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Excess Value Declaration: Refer to Clause 6(4)(B) + (C) on Reverse Side</div>\n                </div>\n                <div class=\"right-box\" style=\"font-size:9px; line-height:1.5;\">\n                    RECEIVED by the Carrier the Goods as specified above in apparent good order and condition unless\n                    otherwise stated, to be transported to such place as agreed, authorised or permitted herein and\n                    subject to all the terms and conditions appearing on the front and reverse of this Bill of Lading to\n                    which the Merchant agrees by accepting this Bill of Lading, any local privileges and customs\n                    notwithstanding.\n                </div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Place and Date of Issue</div>\n                    <div class=\"box-value\">{{place_of_issue}} &nbsp; {{date_of_issue}}</div>\n                </div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Freight Payable at</div>\n                    <div class=\"box-value\">{{freight_payable_at}}</div>\n                </div>\n                <div class=\"right-box\">\n                    <div class=\"box-label\">Shipped on Board Date (Local Time)</div>\n                    <div class=\"box-value\">{{etd}}</div>\n                </div>\n                <div class=\"right-box last\">\n                    <div class=\"box-label\">Signed on Behalf of the Carrier</div>\n                    <div class=\"signed-line\">{{carrier_name}}</div>\n                    <div class=\"box-value\">{{signatory_location}}</div>\n                    <div style=\"margin-top:30px; font-size:9px;\">by ........................................</div>\n                </div>\n            </div>\n        </div>\n\n        <div class=\"action-bar\">\n            <button class=\"btn-print\" onclick=\"window.print();\">PRINT / EXPORT PDF (Official B/L)</button>\n        </div>\n    </div>\n</body>\n\n</html>', 0, 1, '2026-10-03 11:20:18', '2026-10-03 11:20:18'),
(12, 'delivery_order', 'DO V1', '<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title>Delivery Order | {{company_display_name}}</title>\n<style>\n* { box-sizing: border-box; }\nbody {\n    margin: 0;\n    background: #eef2f5;\n    font-family: Arial, Helvetica, sans-serif;\n    padding: 30px 20px;\n    color: #111;\n}\n.do-container {\n    max-width: 1100px;\n    margin: 0 auto;\n    background: #fff;\n    border: 1px solid #ccc;\n    padding: 30px 35px;\n}\n.do-top {\n    text-align: center;\n    border-bottom: 1px solid #ccc;\n    padding-bottom: 14px;\n    margin-bottom: 14px;\n    position: relative;\n}\n.do-top h2 {\n    margin: 0 0 4px;\n    font-size: 15px;\n    letter-spacing: 1px;\n}\n.do-top p {\n    margin: 2px 0;\n    font-size: 11.5px;\n    font-weight: 600;\n}\n.do-top-meta {\n    display: flex;\n    justify-content: space-between;\n    font-size: 10.5px;\n    margin-top: 10px;\n}\n.do-badges {\n    position: absolute;\n    right: 0;\n    top: -4px;\n    display: flex;\n    gap: 10px;\n}\n.do-badges img { height: 55px; }\n\n.do-title-bar {\n    background: #2a2a2a;\n    color: #fff;\n    text-align: center;\n    font-size: 13px;\n    font-weight: 800;\n    letter-spacing: 2px;\n    padding: 8px;\n    margin-bottom: 16px;\n}\n\n.do-head-grid {\n    display: grid;\n    grid-template-columns: 1fr 1fr;\n    gap: 10px 40px;\n    font-size: 11.5px;\n    margin-bottom: 14px;\n}\n.do-head-grid .row { display: flex; margin-bottom: 6px; }\n.do-head-grid .lbl { font-weight: 700; width: 140px; flex-shrink: 0; }\n.do-head-grid .val { font-weight: 500; }\n\n.consignee-block { margin: 16px 0 10px; font-size: 11.5px; }\n.consignee-block .lbl { font-weight: 800; margin-bottom: 4px; }\n.consignee-row { display: flex; margin-bottom: 4px; }\n.consignee-row .k { font-weight: 700; width: 70px; flex-shrink: 0; }\n\n.notify-grid {\n    display: grid;\n    grid-template-columns: 1fr 1.6fr;\n    border: 1px solid #000;\n    margin-top: 10px;\n}\n.notify-head {\n    background: #c9c9c9;\n    font-weight: 700;\n    font-size: 11px;\n    padding: 6px 10px;\n    border-right: 1px solid #000;\n    border-bottom: 1px solid #000;\n    text-decoration: underline;\n}\n.notify-head:last-child { border-right: none; }\n.notify-val {\n    font-size: 11px;\n    padding: 6px 10px;\n    border-right: 1px solid #000;\n}\n.notify-val:last-child { border-right: none; }\n\n.cargo-table {\n    width: 100%;\n    border-collapse: collapse;\n    margin-top: 14px;\n    font-size: 11px;\n}\n.cargo-table th {\n    border: 1px solid #000;\n    background: #fff;\n    text-align: left;\n    padding: 8px 10px;\n    font-weight: 700;\n    font-size: 10.5px;\n}\n.cargo-table td {\n    border: 1px solid #000;\n    padding: 10px 10px;\n    vertical-align: top;\n}\n.cargo-table .meta-line { font-weight: 700; margin-bottom: 2px; }\n\n.remarks-section {\n    margin-top: 16px;\n    font-size: 11.5px;\n}\n.remarks-section .lbl { font-weight: 800; }\n\n.note-section {\n    margin-top: 14px;\n    font-size: 10.5px;\n}\n.note-section .lbl { font-weight: 800; margin-bottom: 4px; }\n.note-section ol { margin: 0; padding-left: 20px; }\n.note-section li { margin-bottom: 3px; }\n\n.do-footer {\n    display: flex;\n    justify-content: space-between;\n    align-items: flex-end;\n    margin-top: 50px;\n    padding-top: 10px;\n    border-top: 1px solid #ccc;\n    font-size: 10px;\n    color: #333;\n}\n.do-footer .left { max-width: 320px; }\n\n.action-bar {\n    background: #f8fafc;\n    padding: 14px;\n    text-align: center;\n    border-top: 1px solid #dee2e6;\n    margin-top: 20px;\n}\n.btn-print {\n    background: #1a365d;\n    color: white;\n    border: none;\n    padding: 8px 32px;\n    font-weight: 700;\n    border-radius: 40px;\n    cursor: pointer;\n}\n.btn-print:hover { background: #2b6cb0; }\n\n@media print {\n    body { background: #fff; padding: 0; }\n    .do-container { border: none; }\n    .btn-print, .action-bar { display: none; }\n}\n@media (max-width: 768px) {\n    .do-head-grid { grid-template-columns: 1fr; }\n    .notify-grid { grid-template-columns: 1fr; }\n}\n</style>\n</head>\n<body>\n<div class=\"do-container\">\n\n    <div class=\"do-top\">\n        {{#if logo_path}}\n        <div class=\"do-badges\"><img src=\"{{logo_path}}\" alt=\"{{company_display_name}}\"></div>\n        {{/if}}\n        <h2>{{company_display_name}}</h2>\n        <p>{{company_address}}</p>\n        <div class=\"do-top-meta\">\n            <span><strong>TEL:</strong> {{company_phone}}</span>\n            <span><strong>EMAIL:</strong> {{company_email}}</span>\n            <span><strong>NATIONAL IDENTITY NO.:</strong> {{company_national_id}}</span>\n        </div>\n    </div>\n\n    <div class=\"do-title-bar\">DELIVERY ORDER</div>\n\n    <div class=\"do-head-grid\">\n        <div>\n            <div class=\"row\"><div class=\"lbl\">To,</div><div class=\"val\">{{do_to}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">VESSEL NAME:</div><div class=\"val\">{{vessel_name}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">VIR NUMBER:</div><div class=\"val\">{{vir_number}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">MBL NUMBER:</div><div class=\"val\">{{tracking_number}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">DESTINATION:</div><div class=\"val\">{{destination}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">IGM NUMBER:</div><div class=\"val\">{{igm_number}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">ISSUED TO:</div><div class=\"val\">{{issued_to}}</div></div>\n        </div>\n        <div>\n            <div class=\"row\"><div class=\"lbl\">DO NUMBER:</div><div class=\"val\">{{do_number}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">ISSUANCE DATE:</div><div class=\"val\">{{issuance_date}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">VALIDITY DATE:</div><div class=\"val\">{{validity_date}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">VOY:</div><div class=\"val\">{{voyage_no}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">HBL NUMBER:</div><div class=\"val\">{{hbl_number}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">MODE OF DELIVERY:</div><div class=\"val\">{{mode_of_delivery}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">DISCHARGE PORT:</div><div class=\"val\">{{pod}}</div></div>\n            <div class=\"row\"><div class=\"lbl\">IGM DATE:</div><div class=\"val\">{{igm_date}}</div></div>\n        </div>\n    </div>\n\n    <div class=\"consignee-block\">\n        <div class=\"lbl\">CONSIGNEE NAME &amp; ADDRESS</div>\n        <div class=\"consignee-row\"><div class=\"k\">Name:</div><div>{{consignee_name}}</div></div>\n        <div class=\"consignee-row\"><div class=\"k\">Address:</div><div>{{consignee_address}}</div></div>\n    </div>\n\n    <div class=\"notify-grid\">\n        <div class=\"notify-head\">NOTIFY PARTY NAME</div>\n        <div class=\"notify-head\">NOTIFY PARTY ADDRESS</div>\n        <div class=\"notify-val\">1. {{notify_name}}</div>\n        <div class=\"notify-val\">{{notify_address}}</div>\n    </div>\n\n    <table class=\"cargo-table\">\n        <thead>\n            <tr>\n                <th style=\"width:14%;\">CONTAINER</th>\n                <th style=\"width:16%;\">EMPTY RETURN</th>\n                <th style=\"width:12%;\">MARKS &amp; NOS</th>\n                <th style=\"width:10%;\">NO. OF PACKAGES</th>\n                <th style=\"width:34%;\">DESCRIPTION</th>\n                <th style=\"width:14%;\">WEIGHT</th>\n            </tr>\n        </thead>\n        <tbody>\n            {{container_rows_html}}\n        </tbody>\n    </table>\n\n    <div class=\"remarks-section\">\n        <div class=\"lbl\">Line Remarks:</div>\n        <div>{{line_remarks}}</div>\n    </div>\n\n    <div class=\"note-section\">\n        <div class=\"lbl\">Note</div>\n        <ol>\n            <li>This letter is to be treated as invalid if any correction/over writing is made.</li>\n            <li>Delivery order is valid, subject to vessel berthing/cargo discharge from said vessel.</li>\n            <li>Delivery order granted subject to endorsements and the Terms &amp; Conditions of Bill of Lading.</li>\n        </ol>\n    </div>\n\n    <div class=\"do-footer\">\n        <div class=\"left\">This is a system generated document and does not require signature and stamp.</div>\n        <div>Printed on: {{printed_on}}</div>\n        <div>Page 1 of 1</div>\n    </div>\n\n    <div class=\"action-bar\">\n        <button class=\"btn-print\" onclick=\"window.print();\">PRINT / EXPORT PDF (Delivery Order)</button>\n    </div>\n</div>\n</body>\n</html>\n', 0, 1, '2026-10-03 11:20:36', '2026-10-03 11:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `shipment_id` int(10) UNSIGNED NOT NULL,
  `shipment_tracking_number` varchar(100) DEFAULT NULL,
  `consignee_name` varchar(255) DEFAULT NULL,
  `consignee_email` varchar(255) DEFAULT NULL,
  `consignee_ntn` varchar(50) DEFAULT NULL,
  `consignee_address` text DEFAULT NULL,
  `consignee_contact` varchar(50) DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `free_days_allowed` int(11) DEFAULT 0,
  `free_days_expiry` date DEFAULT NULL,
  `container_number` varchar(50) DEFAULT NULL,
  `vessel_voyage` varchar(100) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `global_discount` decimal(12,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `invoice_notes` text DEFAULT NULL,
  `status` enum('draft','finalized','sent','paid','partially_paid','overdue','cancelled') DEFAULT 'draft',
  `payment_status` enum('unpaid','partially_paid','paid') DEFAULT 'unpaid',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `finalized_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_email_logs`
--

CREATE TABLE `invoice_email_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_line_items`
--

CREATE TABLE `invoice_line_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `qty_or_days` int(11) DEFAULT 1,
  `rate` decimal(12,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `item_type` enum('detention','demurrage','local_charge','additional_charge','tax','discount') DEFAULT 'local_charge',
  `is_adhoc` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

CREATE TABLE `invoice_payments` (
  `id` int(11) UNSIGNED NOT NULL,
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_sessions`
--

CREATE TABLE `otp_sessions` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `is_verified` tinyint(4) DEFAULT 0,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(30) NOT NULL,
  `booking_no` varchar(50) DEFAULT NULL,
  `company` int(11) DEFAULT NULL,
  `export_reference` varchar(50) DEFAULT NULL,
  `status` enum('pending','picked_up','in_transit','out_for_delivery','delivered','cancelled','booking_confirmed','cargo_received','customs_clearance','vessel_loaded','port_of_discharge','on_hold','exception') DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `actual_delivery` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_cargo`
--

CREATE TABLE `shipment_cargo` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `package_description` text DEFAULT NULL,
  `un_number` varchar(20) DEFAULT NULL,
  `dg_class` varchar(20) DEFAULT NULL,
  `packing_group` varchar(30) DEFAULT NULL,
  `total_pkgs_text` varchar(100) DEFAULT NULL,
  `declared_value` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_cargo_details`
--

CREATE TABLE `shipment_cargo_details` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `bl_type` varchar(20) DEFAULT NULL,
  `delivery_mode` varchar(20) DEFAULT NULL,
  `stuffing_status` varchar(20) DEFAULT NULL,
  `via_port` varchar(150) DEFAULT NULL,
  `plot_number` varchar(50) DEFAULT NULL,
  `container_id` int(10) UNSIGNED DEFAULT NULL,
  `container_no` varchar(128) DEFAULT NULL,
  `goods_description` varchar(255) DEFAULT NULL,
  `hs_code` varchar(20) DEFAULT NULL,
  `commodity_type` varchar(50) DEFAULT NULL,
  `num_packages` int(11) DEFAULT NULL,
  `package_type` varchar(30) DEFAULT NULL,
  `gross_weight` decimal(12,3) DEFAULT NULL,
  `net_weight` decimal(12,3) DEFAULT NULL,
  `volume_cbm` decimal(12,3) DEFAULT NULL,
  `package_dimensions` varchar(50) DEFAULT NULL,
  `marks_numbers` varchar(100) DEFAULT NULL,
  `country_of_origin` varchar(100) DEFAULT NULL,
  `cargo_value` decimal(15,2) DEFAULT NULL,
  `package_description` text DEFAULT NULL,
  `un_number` varchar(20) DEFAULT NULL,
  `dg_class` varchar(20) DEFAULT NULL,
  `packing_group` varchar(30) DEFAULT NULL,
  `total_pkgs_text` varchar(100) DEFAULT NULL,
  `declared_value` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_charges`
--

CREATE TABLE `shipment_charges` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `charge_type` varchar(50) NOT NULL,
  `charge_category` varchar(100) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `expiry_date` date DEFAULT NULL,
  `delay_days` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_consignees`
--

CREATE TABLE `shipment_consignees` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `consignee_name` varchar(150) DEFAULT NULL,
  `consignee_email` varchar(150) DEFAULT NULL,
  `consignee_phone` varchar(30) DEFAULT NULL,
  `consignee_address` text DEFAULT NULL,
  `consignee_city` varchar(100) DEFAULT NULL,
  `consignee_country` varchar(100) DEFAULT NULL,
  `consignee_tax_id` varchar(50) DEFAULT NULL,
  `notify_name` varchar(150) DEFAULT NULL,
  `notify_phone` varchar(30) DEFAULT NULL,
  `notify_address` text DEFAULT NULL,
  `notify_same_as_consignee` varchar(10) DEFAULT 'no',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_containers`
--

CREATE TABLE `shipment_containers` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `container_no` varchar(50) DEFAULT NULL,
  `container_type` varchar(50) DEFAULT NULL,
  `iso_code` varchar(20) DEFAULT NULL,
  `seal_no` varchar(50) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `gross_weight` decimal(12,3) DEFAULT NULL,
  `net_weight` decimal(12,3) DEFAULT NULL,
  `soc` enum('Y','N') DEFAULT 'N',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipment_containers`
--

INSERT INTO `shipment_containers` (`id`, `shipment_id`, `container_no`, `container_type`, `iso_code`, `seal_no`, `origin`, `destination`, `gross_weight`, `net_weight`, `soc`, `created_at`, `updated_at`) VALUES
(38, 11, 'MM12345679', '20\' GP (General Purpose)', NULL, 'SN-852789', 'Pakistan', 'UAE', NULL, NULL, 'N', '2026-10-03 09:16:12', '2026-10-03 09:16:12'),
(37, 11, 'MM12345679', '20\' GP (General Purpose)', NULL, 'SN-852789', 'Pakistan', 'UAE', NULL, NULL, 'N', '2026-10-03 09:16:12', '2026-10-03 09:16:12'),
(36, 10, 'MM12345678', '40\' HC (High Cube)', NULL, 'SN-852789', 'Pakistan', 'Other', NULL, NULL, 'N', '2026-10-03 09:10:14', '2026-10-03 09:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_documents`
--

CREATE TABLE `shipment_documents` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `bl_type` varchar(50) DEFAULT NULL,
  `num_originals` tinyint(4) DEFAULT 3,
  `place_of_issue` varchar(100) DEFAULT NULL,
  `date_of_issue` date DEFAULT NULL,
  `declaration` text DEFAULT NULL,
  `signatory` varchar(100) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `originals_release_at` varchar(100) DEFAULT NULL,
  `freight_payable_at` varchar(100) DEFAULT NULL,
  `no_of_original_bl` varchar(10) DEFAULT '3',
  `issue_place` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_financials`
--

CREATE TABLE `shipment_financials` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `ocean_freight` decimal(12,2) DEFAULT NULL,
  `local_charges` decimal(12,2) DEFAULT NULL,
  `doc_fee` decimal(12,2) DEFAULT NULL,
  `other_charges` decimal(12,2) DEFAULT NULL,
  `freight_in_words` varchar(255) DEFAULT NULL,
  `destination_charges` text DEFAULT NULL,
  `tariff_item_no` varchar(50) DEFAULT NULL,
  `lc_number` varchar(50) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `packing_list_no` varchar(50) DEFAULT NULL,
  `co_no` varchar(50) DEFAULT NULL,
  `customs_no` varchar(50) DEFAULT NULL,
  `insured_by` varchar(30) DEFAULT NULL,
  `insurance_policy` varchar(50) DEFAULT NULL,
  `insurance_value` decimal(15,2) DEFAULT NULL,
  `total_freight` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_items`
--

CREATE TABLE `shipment_items` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `item_description` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `weight` decimal(12,3) DEFAULT NULL,
  `cbm` decimal(12,3) DEFAULT NULL,
  `unit_value` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipment_items`
--

INSERT INTO `shipment_items` (`id`, `shipment_id`, `item_description`, `quantity`, `weight`, `cbm`, `unit_value`, `created_at`) VALUES
(41, 11, 'FFaasdwe', 1, 8.000, 1050.000, 10060.00, '2026-10-03 09:16:12'),
(40, 10, 'FFaasdwe', 1, 55.000, 6.000, 700.00, '2026-10-03 09:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_parties`
--

CREATE TABLE `shipment_parties` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `forwarding_agent` varchar(150) DEFAULT NULL,
  `destination_agent` varchar(150) DEFAULT NULL,
  `destination_agent_address` text DEFAULT NULL,
  `destination_agent_phone` varchar(50) DEFAULT NULL,
  `destination_agent_email` varchar(255) DEFAULT NULL,
  `shipper_ref` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_routes`
--

CREATE TABLE `shipment_routes` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `pol` varchar(150) DEFAULT NULL,
  `pod` varchar(150) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `etd` date DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `mode` varchar(50) DEFAULT NULL,
  `type_of_shipment` varchar(50) DEFAULT NULL,
  `freight_terms` varchar(30) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `incoterms` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_shippers`
--

CREATE TABLE `shipment_shippers` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `shipper_name` varchar(150) DEFAULT NULL,
  `shipper_email` varchar(150) DEFAULT NULL,
  `shipper_phone` varchar(30) DEFAULT NULL,
  `shipper_address` text DEFAULT NULL,
  `shipper_country` varchar(100) DEFAULT NULL,
  `shipper_port` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_transport`
--

CREATE TABLE `shipment_transport` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `vessel_flag` varchar(50) DEFAULT NULL,
  `imo_number` varchar(20) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `carrier_reference_no` varchar(50) DEFAULT NULL,
  `pre_carriage` varchar(100) DEFAULT NULL,
  `place_receipt` varchar(150) DEFAULT NULL,
  `final_destination` varchar(150) DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `seal_no` varchar(30) DEFAULT NULL,
  `place_of_delivery` varchar(150) DEFAULT NULL,
  `mode_load_area` varchar(100) DEFAULT NULL,
  `mode_discharge_area` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_vessels`
--

CREATE TABLE `shipment_vessels` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `vir_number` varchar(100) DEFAULT NULL,
  `igm_number` varchar(100) DEFAULT NULL,
  `igm_date` date DEFAULT NULL,
  `vir_confirmation_date` datetime DEFAULT NULL,
  `vessel_status` varchar(50) DEFAULT NULL,
  `berthed_at` varchar(200) DEFAULT NULL,
  `last_port_of_call` varchar(150) DEFAULT NULL,
  `exp_date_of_arrival` datetime DEFAULT NULL,
  `exp_time_of_arrival` time DEFAULT NULL,
  `exp_departure_date` datetime DEFAULT NULL,
  `exp_departure_time` time DEFAULT NULL,
  `berthing_date` datetime DEFAULT NULL,
  `sailing_date` datetime DEFAULT NULL,
  `vir_launched_by` varchar(150) DEFAULT NULL,
  `vessel_name` varchar(100) DEFAULT NULL,
  `voyage_no` varchar(50) DEFAULT NULL,
  `vessel_flag` varchar(50) DEFAULT NULL,
  `imo_number` varchar(20) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `carrier_reference_no` varchar(50) DEFAULT NULL,
  `pre_carriage` varchar(100) DEFAULT NULL,
  `place_receipt` varchar(150) DEFAULT NULL,
  `place_of_delivery` varchar(150) DEFAULT NULL,
  `seal_no` varchar(30) DEFAULT NULL,
  `mode_load_area` varchar(100) DEFAULT NULL,
  `mode_discharge_area` varchar(100) DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `final_destination` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracking_history`
--

CREATE TABLE `tracking_history` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tracking_history`
--

INSERT INTO `tracking_history` (`id`, `tracking_number`, `status`, `location`, `remarks`, `created_at`) VALUES
(63, 'RGSL-20260825-A6649E', 'pending', '', 'Shipment created', '2026-08-25 11:47:22'),
(62, 'RGSL-20260825-B56A74', 'delivered', 'Karachi', 'Status updated', '2026-08-25 11:35:19'),
(61, 'RGSL-20260825-B56A74', 'pending', 'Karachi', 'Shipment created', '2026-08-25 11:13:47'),
(64, 'RGSL-20260825-B56A74', 'delivered', 'Karachi', 'Status updated', '2026-10-03 09:09:39'),
(65, 'RGSL-20260825-B56A74', 'delivered', 'Karachi', 'Status updated', '2026-10-03 09:10:14'),
(66, 'RGSL-20260825-A6649E', 'in_transit', '', 'Status updated', '2026-10-03 09:16:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','agent','officestaff') DEFAULT 'agent',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `is_active`) VALUES
(1, 'admin', '$2y$10$AXpxszENejoWdzp4.SS52O24HHg3Rgq9fqNzq81Yuae7JyBmOIFrC', 'admin@gmail.com', 'admin', '2026-06-05 10:45:18', 1),
(8, 'aadesh', '$2y$10$z60eOZl7QixPu4fXsxWsHeAMcNU2FMIFxBiiySyz4hPWIpBS0DeIe', 'aadesh.l.manglia@gmail.com', 'agent', '2026-07-01 15:24:15', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `arrival_notices`
--
ALTER TABLE `arrival_notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cargo_release_records`
--
ALTER TABLE `cargo_release_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_company_assignments`
--
ALTER TABLE `document_company_assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_templates`
--
ALTER TABLE `document_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoice_email_logs`
--
ALTER TABLE `invoice_email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoice_line_items`
--
ALTER TABLE `invoice_line_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otp_sessions`
--
ALTER TABLE `otp_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipments_tracking_number` (`tracking_number`),
  ADD KEY `idx_shipments_status` (`status`),
  ADD KEY `idx_shipments_created_at` (`created_at`),
  ADD KEY `idx_shipments_company` (`company`);

--
-- Indexes for table `shipment_cargo`
--
ALTER TABLE `shipment_cargo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_cargo_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_cargo_details`
--
ALTER TABLE `shipment_cargo_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scd_shipment_id` (`shipment_id`),
  ADD KEY `idx_scd_container_id` (`container_id`);

--
-- Indexes for table `shipment_charges`
--
ALTER TABLE `shipment_charges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipment_consignees`
--
ALTER TABLE `shipment_consignees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_consignees_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_containers`
--
ALTER TABLE `shipment_containers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_documents_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_financials`
--
ALTER TABLE `shipment_financials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_financials_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_items`
--
ALTER TABLE `shipment_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipment_parties`
--
ALTER TABLE `shipment_parties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_parties_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_routes`
--
ALTER TABLE `shipment_routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_routes_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_shippers`
--
ALTER TABLE `shipment_shippers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_shippers_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_transport`
--
ALTER TABLE `shipment_transport`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_transport_shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_vessels`
--
ALTER TABLE `shipment_vessels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shipment_vessels_shipment_id` (`shipment_id`);

--
-- Indexes for table `tracking_history`
--
ALTER TABLE `tracking_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `arrival_notices`
--
ALTER TABLE `arrival_notices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cargo_release_records`
--
ALTER TABLE `cargo_release_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `document_company_assignments`
--
ALTER TABLE `document_company_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `document_templates`
--
ALTER TABLE `document_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice_email_logs`
--
ALTER TABLE `invoice_email_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_line_items`
--
ALTER TABLE `invoice_line_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `otp_sessions`
--
ALTER TABLE `otp_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `shipment_cargo`
--
ALTER TABLE `shipment_cargo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `shipment_cargo_details`
--
ALTER TABLE `shipment_cargo_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `shipment_charges`
--
ALTER TABLE `shipment_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `shipment_consignees`
--
ALTER TABLE `shipment_consignees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipment_containers`
--
ALTER TABLE `shipment_containers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipment_financials`
--
ALTER TABLE `shipment_financials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipment_items`
--
ALTER TABLE `shipment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `shipment_parties`
--
ALTER TABLE `shipment_parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipment_routes`
--
ALTER TABLE `shipment_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipment_shippers`
--
ALTER TABLE `shipment_shippers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipment_transport`
--
ALTER TABLE `shipment_transport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `shipment_vessels`
--
ALTER TABLE `shipment_vessels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tracking_history`
--
ALTER TABLE `tracking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `shipment_cargo`
--
ALTER TABLE `shipment_cargo`
  ADD CONSTRAINT `fk_shipment_cargo_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_cargo_details`
--
ALTER TABLE `shipment_cargo_details`
  ADD CONSTRAINT `fk_shipment_cargo_details_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_consignees`
--
ALTER TABLE `shipment_consignees`
  ADD CONSTRAINT `fk_shipment_consignees_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  ADD CONSTRAINT `fk_shipment_documents_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_financials`
--
ALTER TABLE `shipment_financials`
  ADD CONSTRAINT `fk_shipment_financials_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_parties`
--
ALTER TABLE `shipment_parties`
  ADD CONSTRAINT `fk_shipment_parties_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_routes`
--
ALTER TABLE `shipment_routes`
  ADD CONSTRAINT `fk_shipment_routes_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_shippers`
--
ALTER TABLE `shipment_shippers`
  ADD CONSTRAINT `fk_shipment_shippers_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_transport`
--
ALTER TABLE `shipment_transport`
  ADD CONSTRAINT `fk_shipment_transport_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_vessels`
--
ALTER TABLE `shipment_vessels`
  ADD CONSTRAINT `fk_shipment_vessels_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
