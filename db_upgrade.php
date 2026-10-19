<?php
// =====================================================================
// db_upgrade.php  -  brings an OLDER copy of the database up to date
// ---------------------------------------------------------------------
// setup.php calls upgrade_schema() after database.sql has run.
// database.sql already creates any MISSING tables. This file fixes tables
// that exist but are missing newer columns, for example a database that was
// created with an earlier version of the website.
//
// Safe to run many times: every step first checks whether it is needed.
// =====================================================================

function column_exists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $st->execute([DB_NAME, $table, $column]);
    return (int)$st->fetchColumn() > 0;
}

function index_exists(PDO $pdo, string $table, string $index): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $st->execute([DB_NAME, $table, $index]);
    return (int)$st->fetchColumn() > 0;
}

/** Returns a list of the changes it made (empty list = already up to date). */
function upgrade_schema(PDO $pdo): array {
    $done = [];
    $db = '`' . str_replace('`', '', DB_NAME) . '`';

    // 1) quotes.source - shows whether a quote came from the website or the mobile app
    if (!column_exists($pdo, 'quotes', 'source')) {
        $pdo->exec("ALTER TABLE $db.quotes ADD COLUMN source ENUM('website','app') NOT NULL DEFAULT 'website' AFTER message");
        // Older app requests were marked at the end of the message. Label those as "app".
        $pdo->exec("UPDATE $db.quotes SET source = 'app' WHERE message LIKE '%(Sent from the mobile app)'");
        $done[] = 'Added quotes.source (website / app)';
    }
    if (!index_exists($pdo, 'quotes', 'source')) {
        $pdo->exec("ALTER TABLE $db.quotes ADD INDEX source (source)");
    }

    // 2) "Cancelled" status for quotes and projects (re-running this is harmless)
    $pdo->exec("ALTER TABLE $db.quotes MODIFY status ENUM('New','Under Review','Accepted','Rejected','Cancelled','Completed') NOT NULL DEFAULT 'New'");
    $pdo->exec("ALTER TABLE $db.projects MODIFY status ENUM('Not Started','In Progress','On Hold','Completed','Cancelled') NOT NULL DEFAULT 'Not Started'");

    // 3) Project picture + "show on the Services page" switch
    if (!column_exists($pdo, 'projects', 'image_path')) {
        $pdo->exec("ALTER TABLE $db.projects ADD COLUMN image_path VARCHAR(255) NULL AFTER due_date");
        $done[] = 'Added projects.image_path';
    }
    if (!column_exists($pdo, 'projects', 'show_on_services')) {
        $pdo->exec("ALTER TABLE $db.projects ADD COLUMN show_on_services TINYINT(1) NOT NULL DEFAULT 0 AFTER image_path, ADD INDEX show_on_services (show_on_services)");
        $done[] = 'Added projects.show_on_services';
    }

    return $done;
}
