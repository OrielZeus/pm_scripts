<?php

/**
 * My pending actions — PSTools iframe (mockup)
 *
 * Sample inbox before opening a project. Filters by type, priority, and assignment.
 * Markup uses <| … </| so ProcessMaker keeps the HTML when the script is saved.
 *
 * Register this script in PSTools with slug: my-pending-actions
 * Screen iframe:
 *   /api/1.0/pstools/script/my-pending-actions
 */

$html = <<<'HTML'
<|!DOCTYPE html>
<|html lang="en">
<|head>
  <|meta charset="UTF-8" />
  <|meta name="viewport" content="width=device-width, initial-scale=1" />
  <|title>My pending actions<|/title>
  <|style>
    :root {
      --bg: #f6f7f8;
      --surface: #ffffff;
      --border: #e2e5e9;
      --text: #1a1d21;
      --muted: #6b7280;
      --accent: #c41230;
      --accent-soft: #fde8eb;
      --warn: #b45309;
      --info: #1d4ed8;
      --danger: #b91c1c;
      --row-alt: #fafbfc;
      --pill: #eef0f3;
      --pill-active: #1a1d21;
      --pill-active-text: #fff;
      --radius: 8px;
      --font: "Segoe UI", system-ui, -apple-system, sans-serif;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      line-height: 1.4;
    }
    .page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 28px 20px 48px;
    }
    h1 { margin: 0 0 4px; font-size: 1.6rem; font-weight: 650; }
    h2 { margin: 0; font-size: 1.1rem; font-weight: 650; }
    .subtitle { color: var(--muted); font-size: 0.85rem; margin-bottom: 20px; }
    .stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 16px;
    }
    .stat {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 14px 16px;
    }
    .stat .value { font-size: 1.6rem; font-weight: 700; }
    .stat .label { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }
    .stat.info .value { color: var(--info); }
    .stat.warn .value { color: var(--warn); }
    .stat.danger .value { color: var(--danger); }
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      margin-bottom: 16px;
      overflow: hidden;
    }
    .card-h {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
      font-weight: 650;
      font-size: 0.95rem;
    }
    .card-b { padding: 14px 16px; }
    .pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .pill {
      border: 1px solid var(--border);
      background: var(--pill);
      color: var(--text);
      border-radius: 999px;
      padding: 6px 12px;
      font-size: 0.8rem;
      cursor: pointer;
    }
    .pill.active {
      background: var(--pill-active);
      color: var(--pill-active-text);
      border-color: var(--pill-active);
    }
    .filters {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 12px;
    }
    label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; }
    select, input[type="search"] {
      width: 100%;
      height: 36px;
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 0 10px;
      font: inherit;
      background: #fff;
    }
    .hint { font-size: 0.8rem; color: var(--muted); margin-top: 12px; }
    .table-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 8px 0 10px;
    }
    .count { font-size: 0.85rem; color: var(--muted); }
    .table-wrap {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
      min-width: 760px;
    }
    th {
      text-align: left;
      padding: 10px 12px;
      border-bottom: 1px solid var(--border);
      background: var(--surface);
      position: sticky;
      top: 0;
      font-weight: 650;
      white-space: nowrap;
    }
    td {
      padding: 12px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }
    tr:nth-child(even) td { background: var(--row-alt); }
    tr:last-child td { border-bottom: none; }
    .muted { color: var(--muted); }
    .project-cell strong { display: block; font-weight: 600; }
    .go {
      color: var(--accent);
      font-weight: 650;
      text-decoration: none;
      white-space: nowrap;
    }
    .go:hover { text-decoration: underline; }
    .empty {
      padding: 28px;
      text-align: center;
      color: var(--muted);
    }
    .banner {
      background: var(--accent-soft);
      color: #7f1d1d;
      border: 1px solid #f5c2c7;
      border-radius: var(--radius);
      padding: 10px 14px;
      font-size: 0.85rem;
      margin-bottom: 16px;
    }

    .prio-badge {
      display: inline-block;
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 0.72rem;
      font-weight: 700;
      line-height: 1.2;
      white-space: nowrap;
    }
    .prio-badge.immediate { background: #dc3545; color: #fff; }
    .prio-badge.high { background: #ffc107; color: #212529; }
    .prio-badge.medium { background: #28a745; color: #fff; }
    .prio-badge.long { background: #17a2b8; color: #fff; }

    .status-bubble {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      font-size: 0.8rem;
      white-space: nowrap;
    }
    .status-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      flex: 0 0 auto;
    }
    .status-bubble.in_progress { color: #17a2b8; }
    .status-bubble.in_progress .status-dot { background: #17a2b8; }
    .status-bubble.pending_review { color: #c79100; }
    .status-bubble.pending_review .status-dot { background: #e0a800; }
    .status-bubble.needs_info { color: #d97706; }
    .status-bubble.needs_info .status-dot { background: #fd7e14; }
    .status-bubble.not_approved { color: #dc3545; }
    .status-bubble.not_approved .status-dot { background: #dc3545; }
    .status-bubble.approved { color: #28a745; }
    .status-bubble.approved .status-dot { background: #28a745; }
    .status-bubble.draft,
    .status-bubble.missing { color: #6c757d; }
    .status-bubble.draft .status-dot,
    .status-bubble.missing .status-dot { background: #adb5bd; }
    .status-bubble.sent { color: #533939; }
    .status-bubble.sent .status-dot { background: #8b6b5c; }
    .status-bubble.default { color: #495057; }
    .status-bubble.default .status-dot { background: #495057; }

    .pager {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      padding: 12px 14px;
      border-top: 1px solid var(--border);
      background: var(--surface);
    }
    .pager-left, .pager-right {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .pager select {
      width: auto;
      height: 32px;
      min-width: 72px;
    }
    .pager-btn {
      height: 32px;
      min-width: 32px;
      padding: 0 10px;
      border: 1px solid var(--border);
      background: #fff;
      border-radius: 6px;
      font: inherit;
      font-size: 0.8rem;
      cursor: pointer;
      color: var(--text);
    }
    .pager-btn:hover:not(:disabled) { background: var(--pill); }
    .pager-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .pager-btn.active {
      background: var(--pill-active);
      color: var(--pill-active-text);
      border-color: var(--pill-active);
    }
    .pager-info { font-size: 0.8rem; color: var(--muted); }

    @media (max-width: 800px) {
      .stats, .filters { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 520px) {
      .stats, .filters { grid-template-columns: 1fr; }
    }
  <|/style>
<|/head>
<|body>
  <|div class="page">
    <|div class="banner">
      Mockup only - sample data. Proposed "My pending actions" inbox before opening a project.
    <|/div>
    <|h1>My pending actions<|/h1>
    <|p class="subtitle">Login inbox | filters by type / priority / assignment | ordered by priority then newest<|/p>

    <|div class="stats" id="stats"><|/div>

    <|div class="card">
      <|div class="card-h">Filters<|/div>
      <|div class="card-b">
        <|div class="pills" id="scopePills"><|/div>
        <|div class="filters">
          <|div>
            <|label for="typeFilter">Type<|/label>
            <|select id="typeFilter"><|/select>
          <|/div>
          <|div>
            <|label for="priorityFilter">Priority<|/label>
            <|select id="priorityFilter">
              <|option>All priorities<|/option>
              <|option>Immediate<|/option>
              <|option>High<|/option>
              <|option>Medium<|/option>
              <|option>Long<|/option>
            <|/select>
          <|/div>
          <|div>
            <|label for="search">Search<|/label>
            <|input id="search" type="search" placeholder="Artist, project, status..." />
          <|/div>
        <|/div>
        <|p class="hint">Default sort: Immediate -&gt; High -&gt; Medium -&gt; Long, then newest.<|/p>
      <|/div>
    <|/div>

    <|div class="table-head">
      <|h2>Action queue<|/h2>
      <|span class="count" id="rowCount"><|/span>
    <|/div>
    <|div class="table-wrap" id="tableWrap"><|/div>

    <|div class="card" style="margin-top:16px">
      <|div class="card-h">How to read a row<|/div>
      <|div class="card-b">
        <|p style="margin:0 0 8px;font-size:0.9rem">Each row is one actionable item across modules - not a project card.<|/p>
        <|p class="muted" style="margin:0 0 8px;font-size:0.85rem">
          Filters still use assignment logic (you vs your group) under the hood - those columns are hidden to keep the table simpler.
        <|/p>
        <|p class="muted" style="margin:0;font-size:0.85rem">
          Status uses colored dots like Physical Manufacture (In progress, Pending review, Needs info, Approved...).
        <|/p>
      <|/div>
    <|/div>
  <|/div>

  <|script>
    const TASKS = [
      { type: "Project Summary", action: "Finish draft business case", status: "DRAFT", artist: "Elmer O", project: "Project Test", priority: "Medium", why: "You", group: "Project Initiators", initials: "PI", pill: "secondary", updated: "Sep 16 10:05", tone: "info" },
      { type: "Project Summary", action: "Complete required summary fields", status: "INCOMPLETE", artist: "Young Miko", project: "Att.", priority: "High", why: "You", group: "Project Initiators", initials: "PI", pill: "secondary", updated: "Sep 15 08:20", tone: "warning" },
      { type: "Assign FLoR", action: "Assign FLoR contact", status: "MISSING", artist: "Elmer O", project: "Project Test", priority: "Immediate", why: "Your group", group: "FLoR Assignees", initials: "FA", pill: "danger", updated: "Sep 16 09:40", tone: "danger" },
      { type: "Assign FLoR", action: "Assign FLoR contact", status: "MISSING", artist: "Tainy", project: "Data EP", priority: "High", why: "You", group: "FLoR Assignees", initials: "FA", pill: "danger", updated: "Sep 15 16:10", tone: "warning" },
      { type: "Assign FLoR", action: "Update FLoR assignee", status: "MISSING", artist: "Sech", project: "42", priority: "Medium", why: "Your group", group: "FLoR Assignees", initials: "FA", pill: "danger", updated: "Sep 12 11:00", tone: "info" },
      { type: "Project Codes", action: "Enter project / sub codes", status: "MISSING", artist: "Feid", project: "FERXXOCALIPSIS", priority: "Immediate", why: "Your group", group: "Finance Analysts", initials: "FIN", pill: "info", updated: "Sep 16 08:55", tone: "danger" },
      { type: "Project Codes", action: "Enter project / sub codes", status: "MISSING", artist: "Blessd", project: "Por El Mio", priority: "High", why: "You", group: "Finance Analysts", initials: "FIN", pill: "info", updated: "Sep 14 13:22", tone: "warning" },
      { type: "Marketing Budget", action: "Review release budget", status: "PENDING_REVIEW", artist: "Bad Bunny", project: "World Tour 2026", priority: "Immediate", why: "You", group: "Head of Marketing", initials: "HM", pill: "warning", updated: "Sep 16 09:12", tone: "danger" },
      { type: "Marketing Budget", action: "Adjust budget after rejection", status: "NEED_ADJUSTMENT", artist: "Anuel AA", project: "Real Hasta la Muerte", priority: "High", why: "You", group: "Project Managers", initials: "PM", pill: "primary", updated: "Sep 14 19:01", tone: "warning" },
      { type: "Marketing Budget", action: "Editor group review", status: "PENDING_REVIEW", artist: "Myke Towers", project: "La Pantera Negra", priority: "Medium", why: "Your group", group: "Head of Marketing", initials: "HM", pill: "warning", updated: "Sep 13 12:40", tone: "info" },
      { type: "Marketing Plan", action: "Upload marketing plan", status: "MISSING", artist: "Maluma", project: "Don Juan Campaign", priority: "High", why: "Your group", group: "Project Managers", initials: "PM", pill: "primary", updated: "Sep 15 10:18", tone: "warning" },
      { type: "Marketing Plan", action: "Upload marketing plan", status: "MISSING", artist: "Becky G", project: "Esquinas", priority: "Medium", why: "You", group: "Project Managers", initials: "PM", pill: "primary", updated: "Sep 12 08:15", tone: "info" },
      { type: "Physical Manufacture", action: "Approve CD configuration", status: "SUBMITTED", artist: "Rauw Alejandro", project: "Cosa Nuestra Vinyl", priority: "Immediate", why: "You", group: "US Approvers", initials: "USA", pill: "danger", updated: "Sep 16 07:50", tone: "danger" },
      { type: "Physical Manufacture", action: "Provide more information", status: "NEED_MORE_INFORMATION", artist: "Nicky Jam", project: "Infinity Pack", priority: "High", why: "Your group", group: "HP Editors", initials: "HP", pill: "danger", updated: "Sep 15 14:05", tone: "warning" },
      { type: "Physical Manufacture", action: "Creator resubmit formats", status: "IN_PROGRESS", artist: "Daddy Yankee", project: "Legendaddy Box", priority: "Long", why: "You", group: "Physical Creators", initials: "PC", pill: "info", updated: "Sep 10 09:00", tone: "neutral" },
      { type: "DSN", action: "Complete DSN setup", status: "New", artist: "Ozuna", project: "Cosmo Digital", priority: "Immediate", why: "Your group", group: "DSN Submitters", initials: "DSN", pill: "dark", updated: "Sep 16 06:30", tone: "danger" },
      { type: "DSN", action: "Update DSN products", status: "New", artist: "Farruko", project: "La 167", priority: "High", why: "You", group: "DSN Submitters", initials: "DSN", pill: "dark", updated: "Sep 11 17:48", tone: "warning" },
      { type: "Digital Sales", action: "HOS review forecast", status: "SEND_FOR_REVIEW", artist: "J Balvin", project: "Rayo Digital Wave", priority: "Immediate", why: "You", group: "Director of Sales", initials: "DoS", pill: "success", updated: "Sep 16 05:15", tone: "danger" },
      { type: "Digital Sales", action: "HOF second approval", status: "SEND_FOR_REVIEW", artist: "Wisin", project: "Mr. W", priority: "Medium", why: "Your group", group: "Head of Finance", initials: "HoF", pill: "warning", updated: "Sep 13 09:30", tone: "info" },
      { type: "Digital Sales", action: "Submit forecast to P&L", status: "SEND_FOR_PL", artist: "Yandel", project: "Resurrection", priority: "High", why: "You", group: "Director of Sales", initials: "DoS", pill: "success", updated: "Sep 12 15:44", tone: "warning" },
      { type: "Label Copy", action: "Review label copy", status: "PENDING_REVIEW", artist: "Karol G", project: "Manana Sera Bonito Deluxe", priority: "Immediate", why: "You", group: "Label Copy Reviewers", initials: "LCR", pill: "info", updated: "Sep 15 18:40", tone: "danger" },
      { type: "Label Copy", action: "Resubmit after comments", status: "MORE_INFORMATION", artist: "Peso Pluma", project: "Genesis", priority: "High", why: "You", group: "Label Copy Initiators", initials: "LCI", pill: "primary", updated: "Sep 14 11:05", tone: "warning" },
      { type: "Label Copy", action: "Approve label copy", status: "SUBMIT_TO_APPROVE", artist: "Grupo Frontera", project: "El Comienzo", priority: "Medium", why: "Your group", group: "Head of Label Copy", initials: "HLC", pill: "danger", updated: "Sep 13 20:12", tone: "info" },
      { type: "P&L", action: "Finance approval", status: "SUBMITTED_FOR_PL", artist: "Shakira", project: "Las Mujeres Ya No Lloran", priority: "Immediate", why: "You", group: "Head of Finance", initials: "HoF", pill: "warning", updated: "Sep 13 16:55", tone: "danger" },
      { type: "P&L", action: "Setup in console", status: "APPROVED_BY_FINANCE", artist: "Romeo Santos", project: "Formula Vol. 3", priority: "High", why: "Your group", group: "Finance Analysts", initials: "FIN", pill: "info", updated: "Sep 12 14:20", tone: "warning" },
    ];

    const PRIORITY_RANK = { Immediate: 0, High: 1, Medium: 2, Long: 3 };

    const STATUS_META = {
      DRAFT: { label: "Draft", cls: "draft" },
      INCOMPLETE: { label: "Incomplete", cls: "needs_info" },
      MISSING: { label: "Not started", cls: "missing" },
      PENDING_REVIEW: { label: "Pending review", cls: "pending_review" },
      NEED_ADJUSTMENT: { label: "Needs adjustment", cls: "needs_info" },
      SUBMITTED: { label: "Pending review", cls: "pending_review" },
      NEED_MORE_INFORMATION: { label: "Needs more information", cls: "needs_info" },
      MORE_INFORMATION: { label: "Needs more information", cls: "needs_info" },
      IN_PROGRESS: { label: "In progress", cls: "in_progress" },
      New: { label: "New", cls: "in_progress" },
      SEND_FOR_REVIEW: { label: "Sent for review", cls: "pending_review" },
      SEND_FOR_PL: { label: "Sent to P&L", cls: "sent" },
      SUBMIT_TO_APPROVE: { label: "Submitted for approval", cls: "pending_review" },
      SUBMITTED_FOR_PL: { label: "Submitted for P&L", cls: "sent" },
      APPROVED_BY_FINANCE: { label: "Approved by finance", cls: "approved" },
      APPROVED: { label: "Approved", cls: "approved" },
      NOT_APPROVED: { label: "Not approved", cls: "not_approved" },
      RESUBMITTED: { label: "Resubmitted", cls: "pending_review" },
      SETUP_IN_CONSOLE: { label: "Setup in console", cls: "sent" },
      COMPLETED: { label: "Completed", cls: "approved" },
    };

    function statusBubble(code) {
      const meta = STATUS_META[code] || {
        label: String(code).replace(/_/g, " ").toLowerCase().replace(/\b\w/g, function (c) { return c.toUpperCase(); }),
        cls: "default",
      };
      return '<|span class="status-bubble ' + meta.cls + '"><|span class="status-dot"><|/span>' + meta.label + '<|/span>';
    }

    function priorityBadge(priority) {
      var cls = { Immediate: "immediate", High: "high", Medium: "medium", Long: "long" }[priority] || "medium";
      var label = priority === "Long" ? "Long-lead" : priority;
      return '<|span class="prio-badge ' + cls + '">' + label + '<|/span>';
    }

    var TYPES = [
      "All types",
      "Project Summary",
      "Assign FLoR",
      "Project Codes",
      "Marketing Budget",
      "Marketing Plan",
      "Physical Manufacture",
      "DSN",
      "Digital Sales",
      "Label Copy",
      "P&L",
    ];
    var SCOPES = ["All visible", "Assigned to me", "My group"];

    var EXTRA_ARTISTS = [
      ["Rosalia", "Motomami Tour"], ["Bad Gyal", "Sound System"], ["Quevedo", "Donde Quiero Estar"],
      ["Bizarrap", "Bzrp Sessions Pack"], ["Duki", "Desde el Fin del Mundo"], ["C. Tangana", "El Madrileno"],
      ["Aitana", "11 Razones"], ["Rels B", "Happy Birthday Flakko"], ["Nathy Peluso", "Calambre"],
      ["Lola Indigo", "El Dragon"], ["Morad", "Reinsertado"], ["Saiko", "Sakura"],
      ["Mora", "Primer Dia de Clases"], ["Rauw Remix", "Saturno Live"], ["Feid Live", "Ferxxo Nitro"],
      ["Karol Live", "Manana Tour"], ["Ozuna Live", "ENOC Stage"], ["Maluma Live", "Papi Juancho"],
      ["J Balvin Live", "Colores Night"], ["Shakira Live", "Las Mujeres Tour"],
      ["Anuel Live", "Real Tour"], ["Myke Live", "La Pantera Night"], ["Peso Live", "Genesis Stage"],
      ["Grupo Live", "El Comienzo Night"], ["Romeo Live", "Formula Night"], ["Yandel Live", "Resurrection Stage"],
      ["Wisin Live", "Mr W Night"], ["Farruko Live", "La 167 Stage"], ["Sech Live", "42 Night"],
      ["Tainy Live", "Data Night"], ["Blessd Live", "Por El Mio Stage"], ["Becky Live", "Esquinas Night"],
      ["Nicky Live", "Infinity Night"], ["Daddy Live", "Legendaddy Stage"], ["Young Live", "Att. Night"],
    ];
    EXTRA_ARTISTS.forEach(function (pair, i) {
      var base = TASKS[i % TASKS.length];
      var prios = ["Immediate", "High", "Medium", "Long"];
      TASKS.push(Object.assign({}, base, {
        artist: pair[0],
        project: pair[1],
        priority: prios[i % prios.length],
        why: i % 3 === 0 ? "Your group" : "You",
        updated: "Sep " + String(9 - (i % 8)).padStart(2, "0") + " " + (10 + (i % 8)) + ":" + String((i * 7) % 60).padStart(2, "0"),
      }));
    });

    var state = {
      scope: "All visible",
      type: "All types",
      priority: "All priorities",
      search: "",
      page: 1,
      pageSize: 10,
    };

    var typeSelect = document.getElementById("typeFilter");
    TYPES.forEach(function (t) {
      var o = document.createElement("option");
      o.textContent = t;
      typeSelect.appendChild(o);
    });

    var scopePills = document.getElementById("scopePills");
    function renderPills() {
      scopePills.innerHTML = "";
      SCOPES.forEach(function (scope) {
        var b = document.createElement("button");
        b.type = "button";
        b.className = "pill" + (state.scope === scope ? " active" : "");
        b.textContent = scope;
        b.onclick = function () { state.scope = scope; state.page = 1; renderPills(); render(); };
        scopePills.appendChild(b);
      });
    }

    function filtered() {
      return TASKS.filter(function (t) {
        if (state.type !== "All types" && t.type !== state.type) return false;
        if (state.priority !== "All priorities" && t.priority !== state.priority) return false;
        if (state.scope === "Assigned to me" && t.why !== "You") return false;
        if (state.scope === "My group" && t.why !== "Your group") return false;
        if (state.search.trim()) {
          var q = state.search.toLowerCase();
          var hay = (t.artist + " " + t.project + " " + t.action + " " + t.status + " " + t.group + " " + t.type).toLowerCase();
          if (hay.indexOf(q) === -1) return false;
        }
        return true;
      }).sort(function (a, b) {
        var pr = PRIORITY_RANK[a.priority] - PRIORITY_RANK[b.priority];
        if (pr !== 0) return pr;
        return a.updated < b.updated ? 1 : -1;
      });
    }

    function render() {
      var rows = filtered();
      var mine = rows.filter(function (t) { return t.why === "You"; }).length;
      var group = rows.filter(function (t) { return t.why === "Your group"; }).length;
      var immediate = rows.filter(function (t) { return t.priority === "Immediate"; }).length;

      var total = rows.length;
      var pageSize = state.pageSize;
      var totalPages = Math.max(1, Math.ceil(total / pageSize));
      if (state.page > totalPages) state.page = totalPages;
      if (state.page < 1) state.page = 1;
      var start = (state.page - 1) * pageSize;
      var pageRows = rows.slice(start, start + pageSize);
      var from = total === 0 ? 0 : start + 1;
      var to = Math.min(start + pageSize, total);

      document.getElementById("stats").innerHTML =
        '<|div class="stat"><|div class="value">' + total + '<|/div><|div class="label">Visible actions<|/div><|/div>' +
        '<|div class="stat info"><|div class="value">' + mine + '<|/div><|div class="label">Assigned to me<|/div><|/div>' +
        '<|div class="stat warn"><|div class="value">' + group + '<|/div><|div class="label">My group can act<|/div><|/div>' +
        '<|div class="stat danger"><|div class="value">' + immediate + '<|/div><|div class="label">Immediate priority<|/div><|/div>';

      document.getElementById("rowCount").textContent =
        total === 0 ? "0 actions" : ("Showing " + from + "-" + to + " of " + total);

      var wrap = document.getElementById("tableWrap");
      if (!total) {
        wrap.innerHTML = '<|div class="empty">No pending actions for these filters.<|/div>';
        return;
      }

      var pageButtons = [];
      var windowSize = 5;
      var pStart = Math.max(1, state.page - Math.floor(windowSize / 2));
      var pEnd = Math.min(totalPages, pStart + windowSize - 1);
      pStart = Math.max(1, pEnd - windowSize + 1);
      for (var p = pStart; p <= pEnd; p++) {
        pageButtons.push(
          '<|button type="button" class="pager-btn' + (p === state.page ? " active" : "") + '" data-page="' + p + '">' + p + "<|/button>"
        );
      }

      wrap.innerHTML =
        "<|table>" +
          "<|thead><|tr>" +
            "<|th>Priority<|/th><|th>Type<|/th><|th>Action needed<|/th><|th>Status<|/th><|th>Artist / Project<|/th>" +
            '<|th style="text-align:right">Open<|/th>' +
          "<|/tr><|/thead>" +
          "<|tbody>" +
            pageRows.map(function (t) {
              return "<|tr>" +
                "<|td>" + priorityBadge(t.priority) + "<|/td>" +
                "<|td>" + t.type + "<|/td>" +
                "<|td><|strong>" + t.action + "<|/strong><|/td>" +
                "<|td>" + statusBubble(t.status) + "<|/td>" +
                '<|td class="project-cell"><|strong>' + t.artist + '<|/strong><|span class="muted">' + t.project + "<|/span><|/td>" +
                '<|td style="text-align:right"><|a class="go" href="#" onclick="return false">Go -&gt;<|/a><|/td>' +
              "<|/tr>";
            }).join("") +
          "<|/tbody>" +
        "<|/table>" +
        '<|div class="pager">' +
          '<|div class="pager-left">' +
            '<|span class="pager-info">Rows per page<|/span>' +
            '<|select id="pageSizeSelect">' +
              '<|option value="10"' + (pageSize === 10 ? " selected" : "") + ">10<|/option>" +
              '<|option value="25"' + (pageSize === 25 ? " selected" : "") + ">25<|/option>" +
              '<|option value="50"' + (pageSize === 50 ? " selected" : "") + ">50<|/option>" +
            "<|/select>" +
            '<|span class="pager-info">' + from + "-" + to + " of " + total + "<|/span>" +
          "<|/div>" +
          '<|div class="pager-right">' +
            '<|button type="button" class="pager-btn" id="prevPage"' + (state.page <= 1 ? " disabled" : "") + ">Prev<|/button>" +
            pageButtons.join("") +
            '<|button type="button" class="pager-btn" id="nextPage"' + (state.page >= totalPages ? " disabled" : "") + ">Next<|/button>" +
          "<|/div>" +
        "<|/div>";

      document.getElementById("pageSizeSelect").addEventListener("change", function (e) {
        state.pageSize = Number(e.target.value);
        state.page = 1;
        render();
      });
      document.getElementById("prevPage").addEventListener("click", function () {
        state.page -= 1;
        render();
      });
      document.getElementById("nextPage").addEventListener("click", function () {
        state.page += 1;
        render();
      });
      wrap.querySelectorAll("[data-page]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          state.page = Number(btn.getAttribute("data-page"));
          render();
        });
      });
    }

    typeSelect.addEventListener("change", function (e) {
      state.type = e.target.value;
      state.page = 1;
      render();
    });
    document.getElementById("priorityFilter").addEventListener("change", function (e) {
      state.priority = e.target.value;
      state.page = 1;
      render();
    });
    document.getElementById("search").addEventListener("input", function (e) {
      state.search = e.target.value;
      state.page = 1;
      render();
    });

    renderPills();
    render();
  <|/script>
<|/body>
<|/html>
HTML;

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html),
];
