(() => {
  'use strict';

  const JoomlaApi = window.Joomla || {};
  const options = JoomlaApi.getOptions?.('com_xdecaroorganizations.import') || {};
  const strings = options.strings || {};
  const codes = options.codes || {};
  const targets = Array.isArray(options.targets) ? options.targets : [];
  const batchSize = Math.max(1, Math.min(100, Number(options.batchSize || 100)));

  const el = (id) => document.getElementById(id);
  const fileInput = el('xdecaro-organizations-import-file');
  const defaultType = el('xdecaro-organizations-import-default-type');
  const fileInfo = el('xdecaro-organizations-import-file-info');
  const mappingCard = el('xdecaro-organizations-import-mapping-card');
  const mappingBody = el('xdecaro-organizations-import-mapping');
  const analyzeButton = el('xdecaro-organizations-import-analyze');
  const summaryCard = el('xdecaro-organizations-import-summary-card');
  const summary = el('xdecaro-organizations-import-summary');
  const duplicatePanel = el('xdecaro-organizations-import-duplicate-panel');
  const duplicateTitle = el('xdecaro-organizations-import-duplicate-title');
  const duplicateBody = el('xdecaro-organizations-import-duplicate-body');
  const invalidPanel = el('xdecaro-organizations-import-invalid-panel');
  const invalidTitle = el('xdecaro-organizations-import-invalid-title');
  const invalidBody = el('xdecaro-organizations-import-invalid-body');
  const startButton = el('xdecaro-organizations-import-start');
  const progressCard = el('xdecaro-organizations-import-progress-card');
  const progressBar = el('xdecaro-organizations-import-progress');
  const progressText = el('xdecaro-organizations-import-progress-text');
  const reportCard = el('xdecaro-organizations-import-report-card');
  const reportBody = el('xdecaro-organizations-import-report');
  const downloadReportButton = el('xdecaro-organizations-import-download-report');

  if (!fileInput || !mappingBody || !analyzeButton || !startButton) return;

  const state = {
    headers: [],
    rows: [],
    mapping: {},
    encoding: '',
    delimiter: ';',
    candidates: [],
    invalid: [],
    existing: [],
    duplicateRows: [],
    report: [],
    busy: false,
  };

  const clean = (value) => String(value ?? '').trim();

  const normalizeHeader = (value) => String(value || '')
    .replace(/\u00a0/g, ' ')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[_\-./]+/g, ' ')
    .replace(/\s+/g, ' ');

  const aliases = {
    name: ['nome', 'name', 'club', 'squadra', 'team', 'organization', 'organisation', 'organizzazione'],
    legal_name: ['ragione sociale', 'nome legale', 'legal name', 'registered name'],
    code: ['codice', 'code', 'sigla', 'short code', 'club code'],
    type: ['tipo', 'type', 'tipo organizzazione', 'organization type'],
    structure_level: ['livello organizzativo', 'structure level', 'livello'],
    operational_status: ['stato organizzativo', 'operational status'],
    country_code: ['paese', 'nazione', 'country', 'country code', 'country_code', 'iso2', 'nazionalita'],
    city: ['citta', 'città', 'city', 'comune'],
    province: ['provincia', 'province'],
    region: ['regione', 'region'],
    address_line: ['indirizzo', 'address', 'street address'],
    postal_code: ['cap', 'postal code', 'postcode', 'zip'],
    phone: ['telefono', 'phone', 'telephone'],
    email: ['email', 'e mail'],
    website: ['sito web', 'website', 'web', 'url'],
    vat_id: ['partita iva', 'p iva', 'vat', 'vat id'],
    tax_identifier: ['codice fiscale', 'tax identifier', 'tax id', 'tin'],
    affiliation_target: ['federazione', 'federation', 'affiliata a', 'affiliated to', 'organizzazione di riferimento', 'federazione di riferimento'],
    affiliation_type: ['tipo affiliazione', 'affiliation type', 'tipo rapporto'],
    affiliation_code: ['numero affiliazione', 'codice affiliazione', 'affiliation number', 'affiliation code'],
    affiliation_status: ['stato affiliazione', 'affiliation status'],
    affiliation_starts_on: ['affiliazione dal', 'affiliation from', 'data inizio affiliazione'],
    affiliation_ends_on: ['affiliazione al', 'affiliation to', 'data fine affiliazione'],
  };

  const indexesForAliases = (values) => {
    const set = new Set(values.map(normalizeHeader));
    return state.headers
      .filter((header) => set.has(normalizeHeader(header.name)))
      .sort((a, b) => b.nonEmpty - a.nonEmpty || a.index - b.index);
  };

  const bestIndex = (targetKey) => {
    const matches = indexesForAliases(aliases[targetKey] || []);
    return matches.length ? matches[0].index : null;
  };

  const parseCsv = (text, delimiter) => {
    const rows = [];
    let row = [];
    let field = '';
    let quoted = false;

    for (let i = 0; i < text.length; i += 1) {
      const char = text[i];

      if (quoted) {
        if (char === '"' && text[i + 1] === '"') {
          field += '"';
          i += 1;
        } else if (char === '"') {
          quoted = false;
        } else {
          field += char;
        }
        continue;
      }

      if (char === '"') {
        quoted = true;
      } else if (char === delimiter) {
        row.push(field);
        field = '';
      } else if (char === '\n') {
        row.push(field.replace(/\r$/, ''));
        rows.push(row);
        row = [];
        field = '';
      } else {
        field += char;
      }
    }

    row.push(field.replace(/\r$/, ''));
    if (row.some((value) => clean(value) !== '')) rows.push(row);
    return rows;
  };

  const countDelimiter = (line, delimiter) => {
    let quoted = false;
    let count = 0;
    for (let i = 0; i < line.length; i += 1) {
      if (line[i] === '"') {
        if (quoted && line[i + 1] === '"') i += 1;
        else quoted = !quoted;
      } else if (!quoted && line[i] === delimiter) {
        count += 1;
      }
    }
    return count;
  };

  const detectDelimiter = (text) => {
    const line = text.split(/\r?\n/, 1)[0] || '';
    const candidates = [';', ',', '\t'];
    return candidates
      .map((delimiter) => [delimiter, countDelimiter(line, delimiter)])
      .sort((a, b) => b[1] - a[1])[0]?.[0] || ';';
  };

  const decodeFile = async (file) => {
    const buffer = await file.arrayBuffer();
    const bytes = new Uint8Array(buffer);

    try {
      return {
        text: new TextDecoder('utf-8', { fatal: true }).decode(bytes).replace(/^\uFEFF/, ''),
        encoding: 'utf-8',
      };
    } catch (error) {
      return {
        text: new TextDecoder('windows-1252').decode(bytes).replace(/^\uFEFF/, ''),
        encoding: 'windows-1252',
      };
    }
  };

  const resetAnalysis = () => {
    state.candidates = [];
    state.invalid = [];
    state.existing = [];
    state.duplicateRows = [];
    state.report = [];
    summaryCard.hidden = true;
    progressCard.hidden = true;
    reportCard.hidden = true;
    startButton.disabled = true;
    if (duplicatePanel) {
      duplicatePanel.hidden = true;
      duplicatePanel.open = false;
    }
    if (invalidPanel) {
      invalidPanel.hidden = true;
      invalidPanel.open = false;
    }
    duplicateBody?.replaceChildren();
    invalidBody?.replaceChildren();
    reportBody?.replaceChildren();
  };

  const renderMapping = () => {
    mappingBody.replaceChildren();
    state.mapping = {};

    targets.forEach((target) => {
      const tr = document.createElement('tr');
      const fieldCell = document.createElement('td');
      const sourceCell = document.createElement('td');
      const label = document.createElement('span');
      const select = document.createElement('select');

      label.textContent = target.label || target.key;
      if (target.required) {
        const required = document.createElement('span');
        required.textContent = ' *';
        required.className = 'text-danger';
        label.appendChild(required);
      }

      select.className = 'form-select form-select-sm';
      select.dataset.target = target.key;

      const none = document.createElement('option');
      none.value = '';
      none.textContent = strings.notMapped || '— Non importare —';
      select.appendChild(none);

      state.headers.forEach((header) => {
        const option = document.createElement('option');
        option.value = String(header.index);
        option.textContent = header.label;
        select.appendChild(option);
      });

      const index = bestIndex(target.key);
      if (Number.isInteger(index)) {
        select.value = String(index);
        state.mapping[target.key] = index;
      }

      select.addEventListener('change', () => {
        if (select.value === '') delete state.mapping[target.key];
        else state.mapping[target.key] = Number(select.value);
        resetAnalysis();
      });

      fieldCell.appendChild(label);
      sourceCell.appendChild(select);
      tr.append(fieldCell, sourceCell);
      mappingBody.appendChild(tr);
    });
  };

  const sourceRows = () => state.rows.map((source, index) => {
    const row = { _row: index + 2 };
    targets.forEach((target) => {
      const sourceIndex = state.mapping[target.key];
      row[target.key] = Number.isInteger(sourceIndex) ? clean(source[sourceIndex]) : '';
    });
    return row;
  }).filter((row) => Object.entries(row).some(([key, value]) => key !== '_row' && clean(value) !== ''));

  const postPayload = async (url, payload) => {
    const formData = new FormData();
    formData.append(options.token, '1');
    formData.append('payload', JSON.stringify(payload));

    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { Accept: 'application/json' },
      body: formData,
    });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const json = await response.json();
    if (json?.success === false) throw new Error(json.message || strings.requestError || 'Request failed.');
    return json?.data ?? json;
  };

  const translateCode = (code) => codes[code] || code || '';

  const renderSummary = (counts) => {
    const entries = [
      [strings.rows || 'Righe sorgente', counts.total || 0],
      [strings.valid || 'Valide', counts.valid || 0],
      [strings.duplicates || 'Duplicati nel file', counts.duplicate_groups || 0],
      [strings.invalid || 'Da correggere', counts.invalid || 0],
      [strings.existing || 'Già presenti', counts.existing || 0],
      [strings.newOrganizations || 'Nuove organizzazioni', counts.new || 0],
    ];

    summary.replaceChildren();
    entries.forEach(([label, value]) => {
      const item = document.createElement('div');
      item.className = 'xdecaro-import-summary-item';
      const strong = document.createElement('strong');
      const span = document.createElement('span');
      strong.textContent = String(value);
      span.textContent = label;
      item.append(strong, span);
      summary.appendChild(item);
    });
  };

  const renderDuplicates = (summaryData) => {
    if (!duplicatePanel || !duplicateBody || !duplicateTitle) return;
    duplicateBody.replaceChildren();

    if (!state.duplicateRows.length) {
      duplicatePanel.hidden = true;
      return;
    }

    duplicateTitle.textContent = `${state.duplicateRows.length} ${strings.duplicateRowsTitle || 'righe coinvolte'} — ${summaryData.duplicate_groups || 0} ${strings.duplicateGroupsTitle || 'gruppi'}`;

    state.duplicateRows
      .sort((a, b) => Number(a.row || 0) - Number(b.row || 0))
      .forEach((item) => {
        const tr = document.createElement('tr');
        const rowCell = document.createElement('td');
        const nameCell = document.createElement('td');
        const countryCell = document.createElement('td');
        const outcomeCell = document.createElement('td');
        const badge = document.createElement('span');

        rowCell.textContent = String(item.row || '—');
        nameCell.textContent = clean(item.name) || '—';
        countryCell.textContent = clean(item.country_code) || '—';

        if (item.duplicate_status === 'conflict') {
          badge.className = 'badge bg-danger';
          badge.textContent = strings.duplicateConflict || 'Da correggere';
        } else if (item.duplicate_status === 'primary') {
          badge.className = 'badge bg-success';
          badge.textContent = strings.duplicatePrimary || 'Principale';
        } else {
          badge.className = 'badge bg-warning text-dark';
          badge.textContent = strings.duplicateConsolidated || 'Consolidata';
        }

        outcomeCell.appendChild(badge);
        tr.append(rowCell, nameCell, countryCell, outcomeCell);
        duplicateBody.appendChild(tr);
      });

    duplicatePanel.hidden = false;
    duplicatePanel.open = true;
  };

  const renderInvalid = () => {
    if (!invalidPanel || !invalidBody || !invalidTitle) return;
    invalidBody.replaceChildren();

    if (!state.invalid.length) {
      invalidPanel.hidden = true;
      return;
    }

    invalidTitle.textContent = `${state.invalid.length} ${strings.invalidDetailsTitle || 'righe da correggere prima dell’importazione'}`;

    state.invalid
      .sort((a, b) => Number(a.row || 0) - Number(b.row || 0))
      .forEach((item) => {
        const tr = document.createElement('tr');
        const rowCell = document.createElement('td');
        const nameCell = document.createElement('td');
        const countryCell = document.createElement('td');
        const problemCell = document.createElement('td');

        rowCell.textContent = String(item.row || '—');
        nameCell.textContent = clean(item.name) || '—';
        countryCell.textContent = clean(item.country_code) || '—';

        const messages = [];
        if (item.message) messages.push(translateCode(item.message));
        (item.warnings || []).forEach((warning) => messages.push(translateCode(warning)));
        if (item.details) messages.push(String(item.details));
        problemCell.textContent = messages.filter(Boolean).join('; ') || '—';

        tr.append(rowCell, nameCell, countryCell, problemCell);
        invalidBody.appendChild(tr);
      });

    invalidPanel.hidden = false;
    invalidPanel.open = true;
  };

  const statusLabel = (status) => ({
    inserted: strings.statusInserted || 'Importata',
    existing: strings.statusExisting || 'Già presente',
    invalid: strings.statusInvalid || 'Non valida',
    error: strings.statusError || 'Errore',
    duplicate: strings.statusDuplicate || 'Duplicata consolidata',
  }[status] || status);

  const appendReport = () => {
    if (!reportBody) return;
    reportBody.replaceChildren();

    [...state.report]
      .sort((a, b) => Number(a.row || 0) - Number(b.row || 0))
      .forEach((item) => {
        const tr = document.createElement('tr');
        const rowCell = document.createElement('td');
        const statusCell = document.createElement('td');
        const messageCell = document.createElement('td');

        rowCell.textContent = String(item.row || '—');
        statusCell.textContent = statusLabel(item.status);

        const messages = [];
        if (item.message) messages.push(translateCode(item.message));
        (item.warnings || []).forEach((warning) => messages.push(translateCode(warning)));
        if (item.details) messages.push(String(item.details));
        messageCell.textContent = messages.filter(Boolean).join('; ');

        tr.append(rowCell, statusCell, messageCell);
        reportBody.appendChild(tr);
      });
  };

  const updateProgress = (done, total) => {
    const percent = total > 0 ? Math.round((done / total) * 100) : 100;
    progressBar.style.width = `${percent}%`;
    progressBar.textContent = `${percent}%`;
    progressBar.parentElement?.setAttribute('aria-valuenow', String(percent));
    progressText.textContent = `${strings.importing || 'Importazione'} ${done}/${total}`;
  };

  const analyze = async () => {
    if (state.busy) return;

    const missing = targets.filter((target) => target.required && !Number.isInteger(state.mapping[target.key]));
    if (missing.length) {
      window.alert(strings.mappingMissing || 'Manca un campo obbligatorio.');
      return;
    }

    const rows = sourceRows();
    if (!rows.length) {
      window.alert(strings.fileError || 'Nessuna riga valida.');
      return;
    }

    state.busy = true;
    analyzeButton.disabled = true;
    analyzeButton.textContent = strings.analyzing || 'Analisi in corso…';
    resetAnalysis();

    try {
      const data = await postPayload(options.analyzeUrl, {
        rows,
        default_type: defaultType?.value || 'club',
      });

      state.candidates = Array.isArray(data.candidates) ? data.candidates : [];
      state.invalid = Array.isArray(data.invalid) ? data.invalid : [];
      state.existing = Array.isArray(data.existing) ? data.existing : [];
      state.duplicateRows = Array.isArray(data.duplicate_rows) ? data.duplicate_rows : [];

      renderSummary(data.summary || {});
      renderDuplicates(data.summary || {});
      renderInvalid();

      summaryCard.hidden = false;
      startButton.disabled = state.candidates.length === 0;
      analyzeButton.textContent = strings.ready || 'Analisi completata';
    } catch (error) {
      analyzeButton.textContent = strings.requestError || 'Errore';
      window.alert(error?.message || strings.requestError || 'Errore durante l’analisi.');
    } finally {
      state.busy = false;
      analyzeButton.disabled = false;
    }
  };

  const importRows = async () => {
    if (state.busy || !state.candidates.length) return;

    state.busy = true;
    startButton.disabled = true;
    progressCard.hidden = false;
    reportCard.hidden = true;

    const consolidated = state.duplicateRows
      .filter((item) => item.duplicate_status === 'consolidated')
      .map((item) => ({
        row: item.row,
        status: 'duplicate',
        message: 'duplicate_consolidated',
        warnings: [],
      }));

    state.report = [...state.invalid, ...state.existing, ...consolidated];

    let done = 0;
    updateProgress(0, state.candidates.length);

    try {
      for (let index = 0; index < state.candidates.length; index += batchSize) {
        const batch = state.candidates.slice(index, index + batchSize);
        const data = await postPayload(options.batchUrl, {
          rows: batch,
          default_type: defaultType?.value || 'club',
        });

        if (Array.isArray(data.results)) state.report.push(...data.results);
        done += batch.length;
        updateProgress(done, state.candidates.length);
      }

      progressText.textContent = strings.complete || 'Importazione completata';
      startButton.textContent = strings.complete || 'Importazione completata';
    } catch (error) {
      progressText.textContent = error?.message || strings.requestError || 'Errore durante l’importazione.';
    } finally {
      reportCard.hidden = false;
      appendReport();
      state.busy = false;
    }
  };

  const csvEscape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;

  const downloadReport = () => {
    const rows = [
      [strings.reportRow || 'Riga', strings.reportStatus || 'Esito', strings.reportMessage || 'Dettagli'],
      ...[...state.report]
        .sort((a, b) => Number(a.row || 0) - Number(b.row || 0))
        .map((item) => [
          item.row || '',
          statusLabel(item.status),
          [
            ...[item.message, ...(item.warnings || [])].filter(Boolean).map(translateCode),
            item.details || '',
          ].filter(Boolean).join('; '),
        ]),
    ];

    const csv = '\uFEFF' + rows.map((row) => row.map(csvEscape).join(';')).join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = 'organizations-import-report.csv';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
  };

  const loadFile = async () => {
    resetAnalysis();
    mappingCard.hidden = true;
    fileInfo.textContent = '';

    const file = fileInput.files?.[0];
    if (!file) return;

    try {
      const decoded = await decodeFile(file);
      const delimiter = detectDelimiter(decoded.text);
      const matrix = parseCsv(decoded.text, delimiter);

      if (matrix.length < 2 || (matrix[0]?.length || 0) < 1) {
        throw new Error(strings.fileError || 'File CSV non valido.');
      }

      const rawHeaders = matrix.shift().map((value) => clean(value));
      const width = rawHeaders.length;
      const rows = matrix
        .map((row) => {
          const copy = row.slice(0, width);
          while (copy.length < width) copy.push('');
          return copy;
        })
        .filter((row) => row.some((value) => clean(value) !== ''));

      state.rows = rows;
      state.headers = rawHeaders.map((name, index) => ({
        name,
        index,
        nonEmpty: rows.reduce((count, row) => count + (clean(row[index]) !== '' ? 1 : 0), 0),
        label: name,
      }));

      const occurrences = new Map();
      state.headers.forEach((header) => {
        const key = normalizeHeader(header.name);
        occurrences.set(key, (occurrences.get(key) || 0) + 1);
      });

      state.headers.forEach((header, index) => {
        if ((occurrences.get(normalizeHeader(header.name)) || 0) > 1) {
          header.label = `${header.name} (#${index + 1})`;
        }
      });

      state.encoding = decoded.encoding;
      state.delimiter = delimiter;
      renderMapping();
      mappingCard.hidden = false;

      const encodingLabel = decoded.encoding === 'windows-1252'
        ? (strings.encodingCp1252 || 'Windows-1252')
        : (strings.encodingUtf8 || 'UTF-8');

      fileInfo.textContent = `${file.name} — ${rows.length} ${strings.rows || 'righe'} — ${encodingLabel}`;
    } catch (error) {
      state.rows = [];
      state.headers = [];
      fileInfo.textContent = error?.message || strings.fileError || 'File CSV non valido.';
    }
  };

  fileInput.addEventListener('change', loadFile);
  defaultType?.addEventListener('change', resetAnalysis);
  analyzeButton.addEventListener('click', analyze);
  startButton.addEventListener('click', importRows);
  downloadReportButton?.addEventListener('click', downloadReport);
})();
