-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2026 at 02:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

INSERT INTO `companies` (`id`, `company_key`, `name`, `display_name`, `website`, `carrier_name`, `logo_path`, `color_primary`, `color_secondary`, `is_active`, `created_at`, `updated_at`) VALUES
(5, 'royalgulf', 'Royal Gulf Shipping & Logistics', 'Royal Gulf', 'https://royalgulfshipping.com/', '', '/wpcargo/assets/images/logo/logo_1783507142.png', '#137d76', '#f48220', 1, '2026-06-11 15:27:38', '2026-07-08 10:39:02'),
(9, 'messiah-freight', 'Messiah Freight', 'Messiah Freight', 'www.messiahfreight.com', 'MFDL', '/wpcargo/assets/images/logo/logo_1783507114.png', '#34419f', '#f46a17', 1, '2026-07-08 10:38:34', '2026-07-08 10:38:34');

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
(7, 9, 5, 0, '2026-06-18 11:35:48', '2026-06-18 11:35:48');

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
(7, 'bill_of_lading', 'Royal Gulf Bill of Lading', '<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, user-scalable=yes\">\n  <title>Bill of Lading | {{company_display_name}}</title>\n  <style>\n    * {\n      margin: 0;\n      padding: 0;\n      box-sizing: border-box;\n    }\n\n    body {\n      background: #e2e8f0;\n      font-family: \'Inter\', \'Segoe UI\', \'Roboto\', system-ui, -apple-system, sans-serif;\n      padding: 2rem 1.5rem;\n      color: #0a1c2f;\n    }\n\n    /* main document container */\n    .bol-document {\n      max-width: 1280px;\n      margin: 0 auto;\n      background: white;\n      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);\n      border-radius: 4px;\n      transition: all 0.2s;\n    }\n\n    /* inner padding structure (classic bill of lading spacing) */\n    .bol-inner {\n      padding: 2rem 2rem 1.8rem 2rem;\n    }\n\n    /* top branding + title row */\n    .top-row {\n      display: flex;\n      justify-content: space-between;\n      align-items: flex-start;\n      flex-wrap: wrap;\n      border-bottom: 2px solid {{color_primary}};\n      padding-bottom: 1.25rem;\n      margin-bottom: 1.5rem;\n    }\n\n    .shipper-logo h2 {\n      font-size: 1.6rem;\n      font-weight: 700;\n      letter-spacing: -0.3px;\n      color: {{color_primary}};\n      margin-bottom: 0.2rem;\n    }\n    .shipper-logo .sub {\n      font-size: 0.7rem;\n      color: #3b6e8b;\n      font-weight: 500;\n      letter-spacing: 0.3px;\n    }\n    .title-area {\n      text-align: right;\n    }\n    .title-area h1 {\n      font-size: 2rem;\n      font-weight: 800;\n      color: {{color_primary}};\n      letter-spacing: 1px;\n    }\n    .title-area .bl-original {\n      font-size: 0.7rem;\n      background: {{color_primary}};\n      color: white;\n      display: inline-block;\n      padding: 0.2rem 0.8rem;\n      border-radius: 30px;\n      margin-top: 6px;\n      font-weight: 600;\n    }\n\n    /* grid layout for forms (2-col, 3-col) */\n    .two-col-grid {\n      display: grid;\n      grid-template-columns: 1fr 1fr;\n      gap: 1.2rem;\n      margin-bottom: 1.2rem;\n    }\n    .three-col-grid {\n      display: grid;\n      grid-template-columns: repeat(3, 1fr);\n      gap: 1.2rem;\n      margin-bottom: 1.2rem;\n    }\n    .four-col-grid {\n      display: grid;\n      grid-template-columns: repeat(4, 1fr);\n      gap: 1rem;\n      margin-bottom: 1.2rem;\n    }\n\n    /* field boxes — classic shipping document style */\n    .field-group {\n      border: 1px solid #cbd5e1;\n      background: #ffffff;\n      padding: 0.65rem 1rem;\n      border-radius: 0px;\n      transition: 0.1s;\n      box-shadow: 0 1px 1px rgba(0,0,0,0.02);\n    }\n    .field-label {\n      font-size: 0.7rem;\n      font-weight: 700;\n      text-transform: uppercase;\n      letter-spacing: 0.5px;\n      color: #2c5a7a;\n      margin-bottom: 0.35rem;\n    }\n    .field-value {\n      font-size: 0.85rem;\n      font-weight: 600;\n      color: #0f2c3f;\n      line-height: 1.35;\n      word-break: break-word;\n    }\n    .field-sub {\n      font-size: 0.7rem;\n      color: #5e6f8d;\n      margin-top: 4px;\n      font-weight: 400;\n    }\n    .text-uppercase {\n      text-transform: uppercase;\n    }\n    .highlight-number {\n      font-size: 0.95rem;\n      font-weight: 800;\n      color: {{color_secondary}};\n      letter-spacing: 0.3px;\n    }\n    .divider-light {\n      height: 1px;\n      background: #e2e8f0;\n      margin: 1rem 0 1.2rem 0;\n    }\n\n    /* cargo table */\n    .cargo-section {\n      margin: 1.5rem 0 1.3rem 0;\n    }\n    .section-title {\n      font-size: 0.85rem;\n      font-weight: 800;\n      text-transform: uppercase;\n      background: {{color_primary}};\n      color: white;\n      padding: 0.5rem 1rem;\n      letter-spacing: 1px;\n      margin-bottom: 0;\n      border-radius: 2px;\n    }\n    .cargo-table {\n      width: 100%;\n      border-collapse: collapse;\n      font-size: 0.8rem;\n      border: 1px solid #cfdfed;\n    }\n    .cargo-table th {\n      background: #f1f5f9;\n      text-align: left;\n      padding: 0.75rem 0.6rem;\n      font-weight: 700;\n      color: {{color_primary}};\n      border-bottom: 1px solid #cbd5e1;\n      font-size: 0.7rem;\n      letter-spacing: 0.4px;\n    }\n    .cargo-table td {\n      padding: 0.7rem 0.6rem;\n      border-bottom: 1px solid #e9edf2;\n      vertical-align: top;\n      color: #1a2c3e;\n    }\n    .cargo-table tr:last-child td {\n      border-bottom: none;\n    }\n    .total-row {\n      background: #f8fafc;\n      font-weight: 700;\n      border-top: 1px solid #cbd5e1;\n    }\n    .goods-desc-small {\n      font-size: 0.75rem;\n      font-weight: 500;\n      color: #0f2c3f;\n    }\n    .hs-code-text {\n      font-size: 0.7rem;\n      color: #3a6b92;\n      margin-top: 4px;\n    }\n    .freight-term {\n      display: inline-block;\n      background: #eef2ff;\n      font-size: 0.65rem;\n      padding: 2px 8px;\n      border-radius: 12px;\n      font-weight: 700;\n      margin-top: 5px;\n    }\n\n    /* signatures and foot */\n    .footer-grid {\n      display: grid;\n      grid-template-columns: 2fr 1fr;\n      gap: 1.8rem;\n      margin-top: 1.5rem;\n      border-top: 1px solid #cfdfed;\n      padding-top: 1.5rem;\n    }\n    .terms-text {\n      font-size: 0.7rem;\n      color: #334155;\n      line-height: 1.45;\n    }\n    .sign-block {\n      text-align: left;\n    }\n    .sign-line {\n      display: flex;\n      justify-content: space-between;\n      margin-top: 1rem;\n      font-size: 0.7rem;\n      font-weight: 700;\n      color: #2c5a7a;\n      border-top: 1px solid #b9cadb;\n      padding-top: 0.6rem;\n    }\n    .print-btn {\n      text-align: center;\n      background: #f1f5f9;\n      padding: 1rem;\n      border-top: 1px solid #ddd;\n    }\n    .btn-print {\n      background: {{color_primary}};\n      border: none;\n      color: white;\n      font-weight: 600;\n      padding: 0.6rem 2rem;\n      font-size: 0.8rem;\n      border-radius: 40px;\n      cursor: pointer;\n      transition: 0.2s;\n      box-shadow: 0 1px 2px rgba(0,0,0,0.1);\n    }\n    .btn-print:hover {\n      background: {{color_secondary}};\n    }\n\n    @media print {\n      body {\n        background: white;\n        padding: 0;\n        margin: 0;\n      }\n      .bol-document {\n        box-shadow: none;\n        margin: 0;\n        border-radius: 0;\n      }\n      .btn-print, .print-btn {\n        display: none;\n      }\n      .field-group {\n        break-inside: avoid;\n        border: 1px solid #ccc;\n      }\n      .cargo-table th {\n        background: #eef2ff !important;\n        -webkit-print-color-adjust: exact;\n        print-color-adjust: exact;\n      }\n      .section-title {\n        background: {{color_primary}} !important;\n        -webkit-print-color-adjust: exact;\n        print-color-adjust: exact;\n      }\n    }\n\n    /* responsive fixes */\n    @media (max-width: 780px) {\n      .two-col-grid, .three-col-grid, .four-col-grid {\n        grid-template-columns: 1fr;\n        gap: 0.8rem;\n      }\n      .bol-inner {\n        padding: 1.2rem;\n      }\n      .footer-grid {\n        grid-template-columns: 1fr;\n      }\n    }\n    .small-note {\n      font-size: 0.65rem;\n      color: #4a5b6e;\n      margin-top: 3px;\n    }\n  </style>\n</head>\n<body>\n<div class=\"bol-document\">\n  <div class=\"bol-inner\">\n    <!-- HEADER: Shipper / B/L title -->\n    <div class=\"top-row\">\n      <div class=\"shipper-logo\">\n        <h2>{{company_display_name}}</h2>\n        <div class=\"sub\">{{company_tagline}}</div>\n        <div class=\"small-note\" style=\"margin-top: 6px;\">{{company_established}}</div>\n      </div>\n      <div class=\"title-area\">\n        <h1>BILL OF LADING</h1>\n        <div class=\"bl-original\">{{bl_type}}</div>\n        <div class=\"small-note\" style=\"margin-top: 6px;\">{{incoterms}} · Terms {{incoterms_version}}</div>\n      </div>\n    </div>\n\n    <!-- B/L number + booking row (two col) -->\n    <div class=\"two-col-grid\">\n      <div class=\"field-group\">\n        <div class=\"field-label\">BILL OF LADING NO.</div>\n        <div class=\"field-value highlight-number\">{{tracking_number}}</div>\n        <div class=\"field-sub\">Carrier\'s reference</div>\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">BOOKING REF / S/O NO.</div>\n        <div class=\"field-value\">{{booking_no}}</div>\n        <div class=\"field-sub\">Export Ref: {{export_reference}}</div>\n      </div>\n    </div>\n\n    <!-- Shipper + Consignee primary -->\n    <div class=\"two-col-grid\">\n      <div class=\"field-group\">\n        <div class=\"field-label\">SHIPPER (EXPORTER / FULL NAME)</div>\n        <div class=\"field-value\">{{shipper_name}}</div>\n        <div class=\"field-sub\">{{shipper_address}}</div>\n        {{#if shipper_tax_id}}<div class=\"field-sub\">NTN: {{shipper_tax_id}} | GST: {{shipper_gst}}</div>{{/if}}\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">CONSIGNEE (TO ORDER / NAMED)</div>\n        <div class=\"field-value\">{{consignee_name}}</div>\n        <div class=\"field-sub\">{{consignee_address}}</div>\n        {{#if consignee_tax_id}}<div class=\"field-sub\">TRN: {{consignee_tax_id}} </div>{{/if}}\n      </div>\n    </div>\n\n    <!-- Notify party + forwarding agent -->\n    <div class=\"two-col-grid\">\n      <div class=\"field-group\">\n        <div class=\"field-label\">NOTIFY PARTY</div>\n        <div class=\"field-value\">{{notify_name}}</div>\n        <div class=\"field-sub\">{{notify_address}}</div>\n        {{#if notify_phone}}<div class=\"field-sub\">Attn: {{notify_contact}} / Tel: {{notify_phone}}</div>{{/if}}\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">FORWARDING AGENT / BROKER</div>\n        <div class=\"field-value\">{{forwarding_agent}}</div>\n        <div class=\"field-sub\">{{forwarding_agent_address}}</div>\n      </div>\n    </div>\n\n    <!-- Vessel & voyage information -->\n    <div class=\"three-col-grid\">\n      <div class=\"field-group\">\n        <div class=\"field-label\">PORT OF LOADING (POL)</div>\n        <div class=\"field-value\">{{pol}}</div>\n        {{#if etd}}<div class=\"field-sub\">ETD: {{etd}}</div>{{/if}}\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">OCEAN VESSEL / VOYAGE</div>\n        <div class=\"field-value\">{{vessel_name}} / {{voyage_no}}</div>\n        {{#if imo_number}}<div class=\"field-sub\">IMO: {{imo_number}} / Flag: {{vessel_flag}}</div>{{/if}}\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">PORT OF DISCHARGE (POD)</div>\n        <div class=\"field-value\">{{pod}}</div>\n        {{#if eta}}<div class=\"field-sub\">ETA: {{eta}}</div>{{/if}}\n      </div>\n    </div>\n\n    <!-- Place of receipt / Pre carriage / Delivery -->\n    <div class=\"four-col-grid\">\n      <div class=\"field-group\">\n        <div class=\"field-label\">PLACE OF RECEIPT</div>\n        <div class=\"field-value\">{{place_receipt}}</div>\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">PRE-CARRIAGE BY</div>\n        <div class=\"field-value\">{{pre_carriage}}</div>\n        <div class=\"field-sub\">Container# {{container_no}}</div>\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">PLACE OF DELIVERY</div>\n        <div class=\"field-value\">{{place_of_delivery}}</div>\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">FREIGHT PAYABLE AT</div>\n        <div class=\"field-value\">{{freight_payable_at}}</div>\n      </div>\n    </div>\n\n    <!-- Container & Seal numbers + marks -->\n    <div class=\"two-col-grid\">\n      <div class=\"field-group\">\n        <div class=\"field-label\">CONTAINER / SEAL NUMBERS</div>\n        {{#containers}}\n        <div class=\"field-value\" style=\"margin-bottom: 6px;\">{{container_no}} ({{container_type}}) · Seal: {{seal_no}}</div>\n        <div class=\"field-sub\" style=\"margin-top: 0; margin-bottom: 6px;\">{{origin}} → {{destination}}</div>\n        {{/containers}}\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">MARKS & NUMBERS</div>\n        <div class=\"field-value\">{{marks_numbers}}</div>\n        <div class=\"field-sub\">{{package_type}} labelled accordingly</div>\n      </div>\n    </div>\n\n    <!-- CARGO DETAILS - standard goods table -->\n    <div class=\"cargo-section\">\n      <div class=\"section-title\">✦ PARTICULARS FURNISHED BY SHIPPER – CARGO DESCRIPTION</div>\n      <table class=\"cargo-table\">\n        <thead>\n          <tr><th>Marks & Nos.</th><th>No. of Packages</th><th>Description of Goods (HS Code)</th><th>Gross Weight (kg)</th><th>Measurement (CBM)</th></tr>\n        </thead>\n        <tbody>\n          <tr>\n            <td>{{marks_numbers}}</td>\n            <td>{{num_packages}} {{package_type}}</td>\n            <td>\n              <span class=\"goods-desc-small\">{{goods_description}}</span>\n              <div class=\"hs-code-text\">HS Code: {{hs_code}} · {{commodity_type}}</div>\n              <div class=\"freight-term\">{{freight_terms}}</div>\n            </td>\n            <td>{{gross_weight}} KGS</td>\n            <td>{{volume_cbm}} CBM</td>\n          </tr>\n          {{#items}}\n          <tr>\n            <td>—</td>\n            <td>{{quantity}} {{../package_type}}</td>\n            <td>{{item_description}}</td>\n            <td>{{weight}} kg</td>\n            <td>{{cbm}} cbm</td>\n          </tr>\n          {{/items}}\n          <tr class=\"total-row\">\n            <td><strong>TOTAL</strong></td>\n            <td><strong>{{total_packages}} Packages</strong></td>\n            <td><strong>{{package_type}}</strong></td>\n            <td><strong>{{total_weight}} KG</strong></td>\n            <td><strong>{{total_cbm}} CBM</strong></td>\n           </tr>\n        </tbody>\n      </table>\n      <div class=\"small-note\" style=\"margin-top: 8px; padding-left: 2px;\">Shipper\'s declared weight & measurement. Carrier not responsible for unknown particulars.</div>\n    </div>\n\n    <!-- additional shipping terms / service mode -->\n    <div class=\"three-col-grid\" style=\"margin-top: 0.2rem;\">\n      <div class=\"field-group\">\n        <div class=\"field-label\">TRANSPORT MODE</div>\n        <div class=\"field-value\">{{type_of_shipment}}</div>\n        <div class=\"field-sub\">{{mode}}</div>\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">ORIGINALS RELEASED AT</div>\n        <div class=\"field-value\">{{originals_release_at}} ({{no_of_original_bl}}/{{num_originals}} Originals)</div>\n      </div>\n      <div class=\"field-group\">\n        <div class=\"field-label\">TEMPERATURE / REEFER</div>\n        <div class=\"field-value\">{{temperature_requirement}}</div>\n      </div>\n    </div>\n\n    <!-- charges & declaration block classic style -->\n    <div class=\"field-group\" style=\"margin: 1rem 0 0.5rem 0; background: #fefcf5; border-color: #e2d9c3;\">\n      <div class=\"field-label\">FREIGHT & CHARGES SUMMARY</div>\n      <div class=\"field-value\" style=\"font-size: 0.8rem;\">\n        <span>Ocean Freight: {{currency}} {{ocean_freight}} | THC: {{currency}} {{local_charges}} | Documentation fee: {{currency}} {{doc_fee}}</span>\n      </div>\n      <div class=\"field-sub\">{{destination_charges}}</div>\n    </div>\n\n    <div class=\"divider-light\"></div>\n\n    <!-- Footer: Terms and signature area (Standard Bill of Lading format) -->\n    <div class=\"footer-grid\">\n      <div class=\"terms-text\">\n        <strong style=\"font-size: 0.75rem;\">RECEIVED for shipment</strong> in apparent good order and condition unless otherwise noted. The goods to be delivered at the port of discharge or place of delivery mentioned above, subject to all terms & conditions of the carrier\'s Bill of Lading (including liberties, exceptions, and limitation of liability). <br><br>\n        IN WITNESS WHEREOF, {{no_of_original_bl}} ({{num_originals}}) original Bills of Lading have been signed, all of this tenor and date, one of which being accomplished the others to stand void.<br>\n        <span style=\"font-size: 0.65rem;\">{{declaration}}</span>\n      </div>\n      <div class=\"sign-block\">\n        <div><span class=\"field-label\">PLACE & DATE OF ISSUE</span></div>\n        <div class=\"field-value\" style=\"font-size: 0.85rem;\">{{place_of_issue}} – {{date_of_issue}}</div>\n        {{#if etd}}<div style=\"margin-top: 6px;\"><span class=\"field-label\">ON BOARD DATE</span></div>\n        <div class=\"field-value\" style=\"font-size: 0.85rem;\">{{etd}}</div>{{/if}}\n        <div style=\"margin-top: 12px;\"><span class=\"field-label\">CARRIER\'S SIGNATURE</span></div>\n        <div class=\"field-value\">{{carrier_name}}</div>\n        {{#if signatory}}<div class=\"field-sub\">{{signatory}} ({{designation}})</div>{{/if}}\n        <div class=\"sign-line\">\n          <span>As Carrier (Signed by) _______________________</span>\n          <span>As Agent (Digitally verified)</span>\n        </div>\n        <div class=\"small-note\" style=\"margin-top: 8px;\">For and on behalf of Master / Carrier</div>\n      </div>\n    </div>\n\n    <!-- additional field: number of originals -->\n    <div class=\"two-col-grid\" style=\"margin-top: 1rem; border-top: 1px dashed #e2e8f0; padding-top: 1rem;\">\n      <div class=\"field-group\" style=\"background: #fafcff;\">\n        <div class=\"field-label\">NUMBER OF ORIGINAL B/Ls</div>\n        <div class=\"field-value\">{{no_of_original_bl}} ({{num_originals}}) originals issued, non-negotiable unless endorsed</div>\n      </div>\n      <div class=\"field-group\" style=\"background: #fafcff;\">\n        <div class=\"field-label\">SHIPPING TERMS / INCOTERMS</div>\n        <div class=\"field-value\">{{incoterms}} – {{pod}} ({{incoterms_version}})</div>\n      </div>\n    </div>\n  </div> <!-- end bol-inner -->\n\n  <div class=\"print-btn\">\n    <button class=\"btn-print\" onclick=\"window.print();\">🖨️ PRINT BILL OF LADING (PDF)</button>\n  </div>\n</div> <!-- end bol-document -->\n</body>\n</html>', 0, 1, '2026-06-18 11:07:16', '2026-08-24 16:53:58'),
(9, 'bill_of_lading', 'Bill of Lading v3', '<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Bill of Lading Preview</title>\n    <style>\n        * { box-sizing: border-box; }\n        body {\n            margin: 0;\n            background: #eef2f5;\n            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;\n            padding: 30px 20px;\n            color: #111;\n        }\n        .bol-container {\n            max-width: 1260px;\n            margin: 0 auto;\n            background: #fff;\n            border: 1px solid #e2e8f0;\n            box-shadow: 0 20px 35px -12px rgba(0,0,0,.12);\n        }\n        .bol-header {\n            display: flex;\n            justify-content: space-between;\n            align-items: center;\n            padding: 26px 32px 22px;\n            background: #0f3b4f;\n            border-bottom: 3px solid #d67d2f;\n            color: #fff;\n        }\n        .corporate-logo {\n            background: #fff;\n            color: #0f3b4f;\n            display: inline-block;\n            padding: 8px 18px;\n            border-left: 3px solid #d67d2f;\n            font-size: 20px;\n            font-weight: 700;\n            letter-spacing: 1px;\n        }\n        .corporate-logo span {\n            display: block;\n            color: #2c5a74;\n            font-size: 11px;\n            font-weight: 500;\n            letter-spacing: .3px;\n            margin-top: 4px;\n        }\n        .logo-website {\n            color: #d67d2f;\n            font-size: 10px;\n            letter-spacing: .6px;\n            margin-top: 6px;\n            text-transform: uppercase;\n        }\n        .title-section { text-align: right; }\n        .title-section h1 {\n            font-size: 34px;\n            letter-spacing: 5px;\n            margin: 0 0 5px;\n            color: #d67d2f;\n        }\n        .title-section p {\n            font-size: 10px;\n            color: #cbdbe0;\n            letter-spacing: 1px;\n            margin: 0;\n        }\n        .doc-badge {\n            display: inline-block;\n            margin-top: 10px;\n            background: #d67d2f;\n            color: #0f3b4f;\n            padding: 4px 14px;\n            border-radius: 30px;\n            font-size: 8px;\n            font-weight: 800;\n            letter-spacing: 1px;\n        }\n        .grid-2col {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            border-bottom: 1px solid #dee2e6;\n        }\n        .cell {\n            padding: 14px 20px;\n            border-right: 1px solid #dee2e6;\n        }\n        .cell:last-child { border-right: none; }\n        .inner-split {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n        }\n        .sub-cell {\n            padding: 8px 12px;\n        }\n        .sub-cell:first-child {\n            border-right: 1px solid #dee2e6;\n        }\n        .cell-label {\n            font-size: 9px;\n            font-weight: 800;\n            text-transform: uppercase;\n            letter-spacing: 1.2px;\n            color: #2c5a74;\n            margin-bottom: 6px;\n            border-left: 2px solid #d67d2f;\n            padding-left: 7px;\n        }\n        .cell-value {\n            font-size: 12px;\n            font-weight: 700;\n        }\n        .cell-sub {\n            font-size: 9.5px;\n            color: #2d3e50;\n            margin-top: 3px;\n        }\n        .ref-number {\n            font-size: 13px;\n            font-weight: 800;\n            color: #0f3b4f;\n        }\n        .full-width-cell {\n            padding: 14px 22px;\n            border-bottom: 1px solid #dee2e6;\n        }\n        .grid-4col {\n            display: grid;\n            grid-template-columns: 1fr 1.2fr 0.9fr 1fr;\n            border-bottom: 1px solid #dee2e6;\n        }\n        .quad-cell {\n            padding: 12px 16px;\n            border-right: 1px solid #dee2e6;\n        }\n        .quad-cell:last-child { border-right: none; }\n        .cargo-section-title {\n            background: #f4f7fa;\n            padding: 10px 22px;\n            font-size: 11px;\n            font-weight: 800;\n            text-transform: uppercase;\n            letter-spacing: 2px;\n            color: #0f3b4f;\n            border-bottom: 1px solid #dce5ec;\n            border-top: 1px solid #dce5ec;\n        }\n        .goods-header {\n            display: grid;\n            grid-template-columns: 0.8fr 1fr 0.6fr 2.2fr 0.8fr 0.8fr;\n            background: #f8fafc;\n            border-bottom: 1px solid #cfdfed;\n        }\n        .goods-header .goods-col {\n            font-size: 8.5px;\n            font-weight: 800;\n            text-transform: uppercase;\n            padding: 10px 12px;\n            letter-spacing: 0.8px;\n            color: #1e4a6b;\n            border-right: 1px solid #e2edf7;\n        }\n        .goods-row {\n            display: grid;\n            grid-template-columns: 0.8fr 1fr 0.6fr 2.2fr 0.8fr 0.8fr;\n            border-bottom: 1px solid #e9edf2;\n        }\n        .goods-row .goods-cell {\n            padding: 12px 12px;\n            font-size: 9.5px;\n            border-right: 1px solid #eef2f8;\n            word-break: break-word;\n        }\n        .goods-row .goods-cell:last-child { border-right: none; }\n        .marks-text {\n            font-weight: 600;\n            font-size: 9px;\n            line-height: 1.45;\n        }\n        .container-code {\n            font-weight: 700;\n            font-size: 10px;\n            color: #0f3b4f;\n        }\n        .hs-code {\n            display: inline-block;\n            background: #f5f1e6;\n            padding: 2px 6px;\n            border-radius: 12px;\n            margin-top: 6px;\n            font-size: 8.5px;\n        }\n        .freight-note {\n            font-size: 9px;\n            font-weight: 700;\n            color: #b97f10;\n            margin-top: 6px;\n        }\n        .items-wrapper {\n            padding: 16px 24px;\n            background: #fff;\n            border-top: 1px solid #e2e8f0;\n        }\n        .items-title {\n            font-size: 11px;\n            font-weight: 800;\n            text-transform: uppercase;\n            letter-spacing: 2px;\n            color: #0f3b4f;\n            margin-bottom: 12px;\n            border-left: 3px solid #d67d2f;\n            padding-left: 12px;\n        }\n        .item-table {\n            width: 100%;\n            border-collapse: collapse;\n            font-size: 8.5px;\n        }\n        .item-table th {\n            background: #eef3fa;\n            padding: 9px 8px;\n            text-align: left;\n            font-weight: 800;\n            color: #0f3b4f;\n            border: 1px solid #cfdfed;\n        }\n        .item-table td {\n            border: 1px solid #e2e8f0;\n            padding: 8px;\n        }\n        .total-row {\n            background: #f9f5e8;\n            font-weight: 800;\n        }\n        .footer-two {\n            display: grid;\n            grid-template-columns: 2.2fr 1fr;\n            border-top: 2px solid #cfdfed;\n        }\n        .terms-box {\n            padding: 20px 24px;\n            font-size: 7.5px;\n            line-height: 1.6;\n            text-align: justify;\n            border-right: 1px solid #e2e8f0;\n        }\n        .sign-box {\n            padding: 20px 20px;\n        }\n        .sign-place {\n            font-size: 10px;\n            font-weight: 600;\n            margin-bottom: 20px;\n        }\n        .carrier-name-footer {\n            font-weight: 800;\n            font-size: 10px;\n            color: #0f3b4f;\n            margin: 12px 0;\n        }\n        .sign-lines {\n            display: flex;\n            justify-content: space-between;\n            margin-top: 25px;\n            font-size: 7px;\n            font-weight: 800;\n            text-transform: uppercase;\n            color: #3b6a88;\n            border-top: 1px dashed #cbd5e1;\n            padding-top: 14px;\n        }\n        .action-bar {\n            background: #f8fafc;\n            padding: 14px;\n            text-align: center;\n            border-top: 1px solid #dee2e6;\n        }\n        .btn-print {\n            background: #0f3b4f;\n            color: white;\n            border: none;\n            padding: 8px 32px;\n            font-weight: 700;\n            border-radius: 40px;\n            cursor: pointer;\n        }\n        .small-meta {\n            font-size: 7px;\n            color: #4b6a7c;\n            margin-top: 3px;\n        }\n    </style>\n</head>\n<body>\n<div class=\"bol-container\">\n    <div class=\"bol-header\">\n        <div>\n            <div class=\"corporate-logo\">\n                {{company_display_name}}\n                <span>{{company_tagline}}</span>\n            </div>\n            <div class=\"logo-website\">{{company_website}} | {{company_established}}</div>\n        </div>\n        <div class=\"title-section\">\n            <h1>BILL OF LADING</h1>\n            <p>{{bl_type}}</p>\n            <div class=\"doc-badge\">{{incoterms}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Shipper / Exporter</div>\n            <div class=\"cell-value\">{{shipper_name}}</div>\n            <div class=\"cell-sub\">{{shipper_address}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"inner-split\">\n                <div class=\"sub-cell\">\n                    <div class=\"cell-label\">B/L No.</div>\n                    <div class=\"ref-number\">{{tracking_number}}</div>\n                </div>\n                <div class=\"sub-cell\">\n                    <div class=\"cell-label\">Booking No.</div>\n                    <div class=\"cell-value\">{{booking_no}}</div>\n                </div>\n            </div>\n            <div class=\"sub-cell\" style=\"padding: 8px 12px 0; border-top: 1px solid #dee2e6; margin-top: 8px;\">\n                <div class=\"cell-label\">Export Reference</div>\n                <div class=\"cell-sub\">{{export_reference}}</div>\n            </div>\n        </div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Consignee</div>\n            <div class=\"cell-value\">{{consignee_name}}</div>\n            <div class=\"cell-sub\">{{consignee_address}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"cell-label\">Forwarding Agent / NVOCC</div>\n            <div class=\"cell-value\">{{forwarding_agent}}</div>\n            <div class=\"cell-sub\">{{forwarding_agent_address}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Notify Party</div>\n            <div class=\"cell-value\">{{notify_name}}</div>\n            <div class=\"cell-sub\">{{notify_address}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"cell-label\">Place of Receipt / Origin</div>\n            <div class=\"cell-value\">{{place_receipt}}</div>\n        </div>\n    </div>\n\n    <div class=\"full-width-cell\">\n        <div class=\"cell-label\">Destination Agent / Correspondent</div>\n        <div class=\"cell-value\">{{destination_agent}}</div>\n        <div class=\"cell-sub\">{{destination_agent_address}} | Tel: {{destination_agent_phone}} | Email: {{destination_agent_email}}</div>\n    </div>\n\n    <div class=\"grid-2col\">\n        <div class=\"cell\">\n            <div class=\"cell-label\">Port of Loading (POL)</div>\n            <div class=\"cell-value\">{{pol}}</div>\n            <div class=\"cell-sub\">ETD: {{etd}}</div>\n        </div>\n        <div class=\"cell\">\n            <div class=\"cell-label\">Pre-carriage by</div>\n            <div class=\"cell-sub\">{{pre_carriage}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-4col\">\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Ocean Vessel / Voyage</div>\n            <div class=\"cell-value\">{{vessel_name}} / {{voyage_no}}</div>\n            <div class=\"small-meta\">IMO: {{imo_number}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Port of Discharge (POD)</div>\n            <div class=\"cell-value\">{{pod}}</div>\n            <div class=\"small-meta\">ETA: {{eta}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Place of Delivery</div>\n            <div class=\"cell-value\">{{place_of_delivery}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Freight Payable at</div>\n            <div class=\"cell-value\">{{freight_payable_at}}</div>\n        </div>\n    </div>\n\n    <div class=\"grid-4col\">\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Mode of Movement</div>\n            <div class=\"cell-value\">{{type_of_shipment}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Export Carrier</div>\n            <div class=\"cell-value\">{{export_carrier}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Originals Issued at</div>\n            <div class=\"cell-value\">{{originals_release_at}}</div>\n        </div>\n        <div class=\"quad-cell\">\n            <div class=\"cell-label\">Terms of Delivery</div>\n            <div class=\"cell-value\">{{incoterms}}</div>\n        </div>\n    </div>\n\n    <div class=\"cargo-section-title\">▸ PARTICULARS DECLARED BY SHIPPER – CARGO DESCRIPTION</div>\n\n    <div class=\"goods-header\">\n        <div class=\"goods-col\">MARKS & NOS.</div>\n        <div class=\"goods-col\">CONTAINER / SEAL NO.</div>\n        <div class=\"goods-col\">NO. OF PKGS</div>\n        <div class=\"goods-col\">DESCRIPTION OF GOODS</div>\n        <div class=\"goods-col\">GROSS WEIGHT</div>\n        <div class=\"goods-col\">MEASUREMENT</div>\n    </div>\n    <div class=\"goods-row\">\n        <div class=\"goods-cell marks-text\">{{marks_numbers}}</div>\n        <div class=\"goods-cell\">\n            <span class=\"container-code\">{{container_no}} / SEAL-{{seal_no}}</span>\n            <br><span class=\"small-meta\">{{container_type}}</span>\n        </div>\n        <div class=\"goods-cell\">{{num_packages}} {{package_type}}</div>\n        <div class=\"goods-cell\">\n            <strong>{{goods_description}}</strong><br>\n            {{commodity_type}}<br>\n            <span class=\"hs-code\">HS Code: {{hs_code}}</span>\n            <div class=\"freight-note\">{{freight_terms}}</div>\n        </div>\n        <div class=\"goods-cell\"><span style=\"font-weight:700; font-size:11px;\">{{gross_weight}} KGS</span></div>\n        <div class=\"goods-cell\">{{volume_cbm}} CBM</div>\n    </div>\n\n    <div class=\"items-wrapper\">\n        <div class=\"items-title\">PACKAGE ITEMIZATION BREAKDOWN</div>\n        <table class=\"item-table\">\n            <thead>\n                <tr>\n                    <th>Commodity / Article</th>\n                    <th>Cartons / Pkgs</th>\n                    <th>Weight (kg)</th>\n                    <th>Measurement (CBM)</th>\n                    <th>Unit Value ({{currency}})</th>\n                </tr>\n            </thead>\n            <tbody>\n                {{#items}}\n                <tr>\n                    <td>{{item_description}}</td>\n                    <td>{{quantity}}</td>\n                    <td>{{weight}}</td>\n                    <td>{{cbm}}</td>\n                    <td>{{currency}} {{value}}</td>\n                </tr>\n                {{/items}}\n            </tbody>\n            <tfoot>\n                <tr class=\"total-row\">\n                    <td><strong>TOTAL SHIPMENT</strong></td>\n                    <td><strong>{{total_packages}}</strong></td>\n                    <td><strong>{{total_weight}} KGS</strong></td>\n                    <td><strong>{{total_cbm}} CBM</strong></td>\n                    <td><strong>{{currency}} {{total_value}}</strong></td>\n                </tr>\n            </tfoot>\n        </table>\n    </div>\n\n    <div class=\"full-width-cell\" style=\"border-top: 1px solid #e2e8f0; background: #fff;\">\n        <div class=\"cell-label\">Special Instructions / Hazardous Declaration</div>\n        <div class=\"cell-sub\">{{special_instructions}}</div>\n    </div>\n\n    <div class=\"footer-two\">\n        <div class=\"terms-box\">\n            <strong>RECEIVED for shipment</strong> in apparent good order and condition, except as otherwise noted. The total number of packages or units stated herein. This Bill of Lading is issued subject to the Carrier\'s standard terms and conditions. The merchant agrees that this shipment is subject to the Hague-Visby Rules where applicable. In witness whereof, {{no_of_original_bl}} original Bills of Lading have been signed, one of which being accomplished, the others to stand void.\n        </div>\n        <div class=\"sign-box\">\n            <div class=\"sign-place\">PLACE & DATE OF ISSUANCE: <strong>{{place_of_issue}} – {{date_of_issue}}</strong></div>\n            <div class=\"sign-place\">ON BOARD DATE: <strong>{{etd}}</strong></div>\n            <div class=\"carrier-name-footer\">{{carrier_name}}<br>As Carrier</div>\n            <div class=\"small-meta\">Signatory: {{signatory}} ({{designation}})</div>\n            <div class=\"sign-lines\">\n                <span>Authorized Signature</span>\n                <span>Electronically Verified</span>\n            </div>\n            <div class=\"small-meta\" style=\"margin-top:10px;\">{{bl_type}} – Issued at {{place_of_issue}}</div>\n        </div>\n    </div>\n\n    <div class=\"action-bar\">\n        <button class=\"btn-print\" onclick=\"window.print();\">PRINT / EXPORT PDF (Official B/L)</button>\n    </div>\n</div>\n</body>\n</html>', 0, 1, '2026-06-18 11:35:37', '2026-08-24 16:37:37');

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

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `tracking_number`, `booking_no`, `company`, `export_reference`, `status`, `current_location`, `estimated_delivery`, `actual_delivery`, `created_at`, `updated_at`) VALUES
(10, 'RGSL-20260825-B56A74', 'BK-547893214', 5, 'EXP-REF-452178963', 'delivered', 'Karachi', '2026-08-26', '2026-08-28', '2026-08-25 11:13:47', '2026-08-25 11:35:19'),
(11, 'RGSL-20260825-A6649E', 'BK-547893214', 5, 'EXP-REF-452178963', 'in_transit', NULL, '2026-09-03', '2026-09-05', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

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

--
-- Dumping data for table `shipment_cargo`
--

INSERT INTO `shipment_cargo` (`id`, `shipment_id`, `package_description`, `un_number`, `dg_class`, `packing_group`, `total_pkgs_text`, `declared_value`, `created_at`, `updated_at`) VALUES
(7, 10, NULL, 'UN-123', 'Class12345', 'PG I (High Danger)', NULL, NULL, '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(9, 11, NULL, 'UN-123', 'Class12345', 'PG I (High Danger)', NULL, NULL, '2026-08-25 11:47:22', '2026-08-25 11:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_cargo_details`
--

CREATE TABLE `shipment_cargo_details` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
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

--
-- Dumping data for table `shipment_cargo_details`
--

INSERT INTO `shipment_cargo_details` (`id`, `shipment_id`, `container_id`, `container_no`, `goods_description`, `hs_code`, `commodity_type`, `num_packages`, `package_type`, `gross_weight`, `net_weight`, `volume_cbm`, `package_dimensions`, `marks_numbers`, `country_of_origin`, `cargo_value`, `package_description`, `un_number`, `dg_class`, `packing_group`, `total_pkgs_text`, `declared_value`, `created_at`, `updated_at`) VALUES
(10, 10, 32, 'MM12345678', 'Textile Fabric', '5208', 'Commodity Type', 9, 'Cloth', 5.000, 6.000, 56.000, 'Package Dimensions', '456321', 'Pakistan', 500.00, 'Hi this is package descripation', 'UN Number', 'DG Class', 'Packing Group', 'Total Pkgs Text', '999', '2026-08-25 08:35:19', '2026-08-25 08:35:19'),
(11, 11, 33, 'MM12345679', 'Textile Fabric', '5208', 'Textile', 55, 'Cloth', 7.000, 6.000, 67.000, '10x50x80', '456321', 'Pakistan', 4.00, 'Package Description', '4', '4', '4', '2', '4', '2026-08-25 08:47:22', '2026-08-25 08:47:22'),
(12, 11, 34, 'MM12345677', 'Scrap Cloth', '5044', 'Iron', 5, '2', 9.000, 8.000, 1050.000, '20x50x80', '4', 'Pakistan', 8.00, 'Package Description fsd', '8', '65', '4', '1', '4', '2026-08-25 08:47:22', '2026-08-25 08:47:22');

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
  `consignee_tax_id` varchar(50) DEFAULT NULL,
  `notify_name` varchar(150) DEFAULT NULL,
  `notify_phone` varchar(30) DEFAULT NULL,
  `notify_address` text DEFAULT NULL,
  `notify_same_as_consignee` varchar(10) DEFAULT 'no',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipment_consignees`
--

INSERT INTO `shipment_consignees` (`id`, `shipment_id`, `consignee_name`, `consignee_email`, `consignee_phone`, `consignee_address`, `consignee_tax_id`, `notify_name`, `notify_phone`, `notify_address`, `notify_same_as_consignee`, `created_at`, `updated_at`) VALUES
(8, 10, 'Royal Gulf Shipping', 'inforoyalgulf@gmail.com', '+923032730674', 'Fazliat Mansion, 7 Street', '45612378', 'Royal Gulf Shipping', '+923032730674', 'Fazliat Mansion, 7 Street', 'yes', '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(10, 11, 'Royal Gulf Shipping', 'aadesh.l.manglia@gmail.com', '+923032730674', 'Fazliat Mansion, 7 Street', '45612378', 'Royal Gulf Shipping', '+923032730674', 'Fazliat Mansion, 7 Street', 'yes', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_containers`
--

CREATE TABLE `shipment_containers` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `container_no` varchar(50) DEFAULT NULL,
  `container_type` varchar(50) DEFAULT NULL,
  `seal_no` varchar(50) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipment_containers`
--

INSERT INTO `shipment_containers` (`id`, `shipment_id`, `container_no`, `container_type`, `seal_no`, `origin`, `destination`, `created_at`, `updated_at`) VALUES
(34, 11, 'MM12345677', '40\' HC (High Cube)', 'SN-8527855', 'India', 'Pakistan', '2026-08-25 11:47:22', '2026-08-25 11:47:22'),
(33, 11, 'MM12345679', '20\' GP (General Purpose)', 'SN-852789', 'Pakistan', 'UAE', '2026-08-25 11:47:22', '2026-08-25 11:47:22'),
(32, 10, 'MM12345678', '40\' HC (High Cube)', 'SN-852789', 'Pakistan', 'Other', '2026-08-25 11:35:19', '2026-08-25 11:35:19');

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

--
-- Dumping data for table `shipment_documents`
--

INSERT INTO `shipment_documents` (`id`, `shipment_id`, `bl_type`, `num_originals`, `place_of_issue`, `date_of_issue`, `declaration`, `signatory`, `designation`, `special_instructions`, `comments`, `originals_release_at`, `freight_payable_at`, `no_of_original_bl`, `issue_place`, `issue_date`, `created_at`, `updated_at`) VALUES
(8, 10, 'Original BL (3 Originals)', 3, 'Karachi, Pakistan', '2026-08-27', 'Shipper\'s Declaration', 'Authorized Signatory', 'Mannager', 'Special Instructions', 'Internal Comments (not printed on B/L)', 'Dubai', 'Dubai', '3', 'Jebile ALi', '2026-08-29', '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(10, 11, 'Original BL (3 Originals)', 3, 'Karachi, Pakistan', '2026-09-12', 'Shipper\'s Declaration', 'Authorized Signatory', 'Mannager', 'Special Instructions', 'Internal Comments (not printed on B/L)', 'Dubai', 'Dubai', '3', 'Jebile ALi', '2026-09-10', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

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

--
-- Dumping data for table `shipment_financials`
--

INSERT INTO `shipment_financials` (`id`, `shipment_id`, `ocean_freight`, `local_charges`, `doc_fee`, `other_charges`, `freight_in_words`, `destination_charges`, `tariff_item_no`, `lc_number`, `invoice_no`, `packing_list_no`, `co_no`, `customs_no`, `insured_by`, `insurance_policy`, `insurance_value`, `total_freight`, `created_at`, `updated_at`) VALUES
(8, 10, 50.00, 40.00, 30.00, 60.00, 'one eighty dihram', 'NO Destination charges', 'Freight-Preparid', 'LC-444777', 'IN-444568', '777', '457890', '5562', 'Consignee', '789631452', 62000.00, 180.00, '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(10, 11, 66.00, 80.00, 40.00, 50.00, 'one eighty dihram', 'Destination Charges', 'Freight-Preparid', 'LC-444777', 'IN-444568', '777', '457890', '5562', 'Not Insured', NULL, NULL, 236.00, '2026-08-25 11:47:22', '2026-08-25 11:47:22');

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
(38, 11, 'FFaasdwe', 1, 8.000, 1050.000, 10060.00, '2026-08-25 11:47:22'),
(37, 10, 'FFaasdwe', 1, 55.000, 6.000, 700.00, '2026-08-25 11:35:19');

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

--
-- Dumping data for table `shipment_parties`
--

INSERT INTO `shipment_parties` (`id`, `shipment_id`, `forwarding_agent`, `destination_agent`, `destination_agent_address`, `destination_agent_phone`, `destination_agent_email`, `shipper_ref`, `created_at`, `updated_at`) VALUES
(8, 10, 'Rana', 'Saad', 'Fazliat Mansion, 7 Street', '+923032730674', 'touseefroyalgulf@gmail.com', 'SH-REF-101', '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(10, 11, 'Rana', 'Saad', 'Fazliat Mansion, 7 Street', '+923032730674', 'aadesh.l.manglia@gmail.com', 'SH-REF-101', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

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

--
-- Dumping data for table `shipment_routes`
--

INSERT INTO `shipment_routes` (`id`, `shipment_id`, `pol`, `pod`, `origin`, `destination`, `etd`, `eta`, `pickup_date`, `pickup_time`, `mode`, `type_of_shipment`, `freight_terms`, `payment_mode`, `currency`, `incoterms`, `created_at`, `updated_at`) VALUES
(8, 10, 'Karachi', 'Dubai, UAE', 'Pakistan', 'Other', '2026-08-31', '2026-09-14', '2026-09-16', '19:00:00', 'Sea Transport (FCL)', 'FCL (Full Container Load)', 'Collect', 'Bank Transfer (TT)', 'AED', 'EXW – Ex Works', '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(10, 11, 'Karachi', 'Dubai, UAE', 'Pakistan', 'UAE', '2026-08-29', '2026-09-01', '2026-09-03', '22:46:00', 'Sea Transport (FCL)', 'FCL (Full Container Load)', 'Collect', 'Cash', 'USD', 'FOB – Free on Board', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipment_shippers`
--

INSERT INTO `shipment_shippers` (`id`, `shipment_id`, `shipper_name`, `shipper_email`, `shipper_phone`, `shipper_address`, `created_at`, `updated_at`) VALUES
(8, 10, 'Aadesh', 'aadesh.l.manglia@gmail.com', '+923032730674', 'Fazliat Mansion, 7 Street', '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(10, 11, 'Aadesh', 'aadesh.l.manglia@gmail.com', '+923032730674', 'Fazliat Mansion, 7 Street', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

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

--
-- Dumping data for table `shipment_transport`
--

INSERT INTO `shipment_transport` (`id`, `shipment_id`, `vessel_flag`, `imo_number`, `carrier`, `carrier_reference_no`, `pre_carriage`, `place_receipt`, `final_destination`, `departure_time`, `pickup_date`, `pickup_time`, `seal_no`, `place_of_delivery`, `mode_load_area`, `mode_discharge_area`, `created_at`, `updated_at`) VALUES
(7, 10, 'Pakistan', 'IMO-56321789654', 'Maersk Line', 'CRR-005-003', 'Pre Carriage Evergreen', 'Qasim', 'Dubai', '10:00:00', '2026-09-16', '19:00:00', 'SN-852789', 'Karachi', 'Port Qasim', 'Port Qasim Area', '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(9, 11, 'Pakistan', 'IMO-56321789654', 'Maersk Line', 'CRR-005-003', 'Pre Carriage Evergreen', 'Qasim', 'Dubai', '21:00:00', '2026-09-03', '22:46:00', 'SN-852789', 'Karachi', 'Port Qasim', 'Port Qasim Area', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_vessels`
--

CREATE TABLE `shipment_vessels` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
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

--
-- Dumping data for table `shipment_vessels`
--

INSERT INTO `shipment_vessels` (`id`, `shipment_id`, `vessel_name`, `voyage_no`, `vessel_flag`, `imo_number`, `carrier`, `carrier_reference_no`, `pre_carriage`, `place_receipt`, `place_of_delivery`, `seal_no`, `mode_load_area`, `mode_discharge_area`, `departure_time`, `pickup_date`, `pickup_time`, `final_destination`, `created_at`, `updated_at`) VALUES
(8, 10, 'Markes', 'VOY-2025-M5', 'Pakistan', 'IMO-56321789654', 'Maersk Line', 'CRR-005-003', 'Pre Carriage Evergreen', 'Qasim', 'Karachi', 'SN-852789', 'Port Qasim', 'Port Qasim Area', '10:00:00', '2026-09-16', '19:00:00', 'Dubai', '2026-08-25 11:13:47', '2026-08-25 11:13:47'),
(10, 11, 'Markes', 'VOY-2025-M5', 'Pakistan', 'IMO-56321789654', 'Maersk Line', 'CRR-005-003', 'Pre Carriage Evergreen', 'Qasim', 'Karachi', 'SN-852789', 'Port Qasim', 'Port Qasim Area', '21:00:00', '2026-09-03', '22:46:00', 'Dubai', '2026-08-25 11:47:22', '2026-08-25 11:47:22');

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
(61, 'RGSL-20260825-B56A74', 'pending', 'Karachi', 'Shipment created', '2026-08-25 11:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `is_active`) VALUES
(1, 'admin', '$2y$10$AXpxszENejoWdzp4.SS52O24HHg3Rgq9fqNzq81Yuae7JyBmOIFrC', 'admin@gmail.com', 'admin', '2026-06-05 10:45:18', 1),
(8, 'aadesh', '$2y$10$/ZIqDJ4Lb6GdBNwRS3QXOO3xotwWJOOMwNU50E1/N2WcmVBX/daBC', 'aadesh.l.manglia@gmail.com', 'staff', '2026-07-01 15:24:15', 1),
(11, 'touseef', '$2y$10$iJ0tnRaCRvdv0.vRyh9Jueov8WtjUFktZcJB3DmwP44yjXr48637.', 'touseefbembex@gmail.com', 'staff', '2026-08-24 14:41:01', 1);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `document_company_assignments`
--
ALTER TABLE `document_company_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `document_templates`
--
ALTER TABLE `document_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `shipment_cargo`
--
ALTER TABLE `shipment_cargo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shipment_cargo_details`
--
ALTER TABLE `shipment_cargo_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `shipment_charges`
--
ALTER TABLE `shipment_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `shipment_consignees`
--
ALTER TABLE `shipment_consignees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shipment_containers`
--
ALTER TABLE `shipment_containers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `shipment_documents`
--
ALTER TABLE `shipment_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shipment_financials`
--
ALTER TABLE `shipment_financials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shipment_items`
--
ALTER TABLE `shipment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `shipment_parties`
--
ALTER TABLE `shipment_parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shipment_routes`
--
ALTER TABLE `shipment_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shipment_shippers`
--
ALTER TABLE `shipment_shippers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shipment_transport`
--
ALTER TABLE `shipment_transport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shipment_vessels`
--
ALTER TABLE `shipment_vessels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tracking_history`
--
ALTER TABLE `tracking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
