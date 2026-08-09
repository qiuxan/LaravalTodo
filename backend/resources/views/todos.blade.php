<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Todo</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f7fb;
        }

        button,
        input,
        textarea {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        .page {
            width: min(960px, calc(100% - 32px));
            margin: 0 auto;
            padding: 40px 0;
        }

        .header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .header h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.1;
        }

        .header p {
            margin: 8px 0 0;
            color: #5d6980;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #dce3ee;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(23, 32, 51, 0.08);
        }

        .create-form {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(220px, 1.4fr) auto;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid #e6ebf3;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            color: #5d6980;
            font-size: 13px;
            font-weight: 600;
        }

        .field input,
        .field textarea {
            width: 100%;
            border: 1px solid #c9d3e2;
            border-radius: 6px;
            padding: 10px 12px;
            color: #172033;
            background: #ffffff;
        }

        .field textarea {
            min-height: 42px;
            resize: vertical;
        }

        .primary-button {
            align-self: end;
            border: 0;
            border-radius: 6px;
            padding: 11px 16px;
            color: #ffffff;
            background: #2463eb;
            font-weight: 700;
        }

        .primary-button:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-bottom: 1px solid #e6ebf3;
        }

        .import-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            border-bottom: 1px solid #e6ebf3;
            background: #fbfcff;
        }

        .import-copy {
            display: grid;
            gap: 4px;
        }

        .import-title {
            margin: 0;
            font-size: 15px;
            font-weight: 750;
        }

        .import-status {
            margin: 0;
            color: #5d6980;
            font-size: 14px;
        }

        .filters {
            display: inline-flex;
            gap: 4px;
            padding: 4px;
            border: 1px solid #d7deea;
            border-radius: 8px;
            background: #f8fafc;
        }

        .filter-button {
            border: 0;
            border-radius: 6px;
            padding: 8px 12px;
            color: #5d6980;
            background: transparent;
            font-weight: 650;
        }

        .filter-button.active {
            color: #172033;
            background: #ffffff;
            box-shadow: 0 1px 5px rgba(23, 32, 51, 0.12);
        }

        .status {
            color: #5d6980;
            font-size: 14px;
        }

        .error {
            display: none;
            margin: 0 16px 16px;
            padding: 10px 12px;
            border: 1px solid #f0b5b5;
            border-radius: 6px;
            color: #8a1f1f;
            background: #fff1f1;
        }

        .error.visible {
            display: block;
        }

        .todo-list {
            display: grid;
        }

        .todo-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 12px;
            align-items: start;
            padding: 16px;
            border-bottom: 1px solid #e6ebf3;
        }

        .todo-row:last-child {
            border-bottom: 0;
        }

        .todo-check {
            width: 20px;
            height: 20px;
            margin-top: 4px;
        }

        .todo-title {
            margin: 0;
            font-size: 17px;
        }

        .todo-description {
            margin: 4px 0 0;
            color: #5d6980;
            line-height: 1.45;
        }

        .todo-row.completed .todo-title,
        .todo-row.completed .todo-description {
            color: #8a94a6;
            text-decoration: line-through;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .secondary-button,
        .danger-button {
            border: 1px solid #c9d3e2;
            border-radius: 6px;
            padding: 8px 10px;
            background: #ffffff;
            color: #172033;
            font-weight: 650;
        }

        .danger-button {
            border-color: #f0b5b5;
            color: #a52222;
        }

        .edit-form {
            display: grid;
            gap: 10px;
        }

        .edit-actions {
            display: flex;
            gap: 8px;
        }

        .empty {
            padding: 32px 16px;
            text-align: center;
            color: #5d6980;
        }

        @media (max-width: 720px) {
            .page {
                width: min(100% - 20px, 960px);
                padding: 24px 0;
            }

            .header,
            .import-panel,
            .toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .create-form {
                grid-template-columns: 1fr;
            }

            .primary-button {
                width: 100%;
            }

            .todo-row {
                grid-template-columns: auto 1fr;
            }

            .actions {
                grid-column: 2;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <div>
                <h1>Laravel Todo</h1>
                <p>Blade UI calling the Laravel JSON API.</p>
            </div>
            <div class="status" id="summary">Loading todos...</div>
        </header>

        <section class="panel" aria-label="Todo application">
            <form class="create-form" id="create-form">
                <div class="field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" maxlength="255" required placeholder="What needs to be done?">
                </div>
                <div class="field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Optional details"></textarea>
                </div>
                <button class="primary-button" id="create-button" type="submit">Add Todo</button>
            </form>

            <div class="import-panel" aria-label="DummyJSON import">
                <div class="import-copy">
                    <p class="import-title">DummyJSON import</p>
                    <p class="import-status" id="import-status">No import started.</p>
                </div>
                <button class="secondary-button" id="import-button" type="button">Import from DummyJSON</button>
            </div>

            <div class="toolbar">
                <div class="filters" aria-label="Todo filters">
                    <button class="filter-button active" type="button" data-filter="all">All</button>
                    <button class="filter-button" type="button" data-filter="active">Active</button>
                    <button class="filter-button" type="button" data-filter="completed">Completed</button>
                </div>
                <div class="status" id="loading-state">Ready</div>
            </div>

            <p class="error" id="error-box"></p>
            <div class="todo-list" id="todo-list"></div>
        </section>
    </main>

    <script>
        const apiBase = '/api/todos';
        const importApiBase = '/api/integrations/dummy-json/todos/import';
        const state = {
            todos: [],
            filter: 'all',
            editingId: null,
            import: {
                id: null,
                status: 'idle',
                importedCount: 0,
                errorMessage: null,
            },
        };

        const listEl = document.querySelector('#todo-list');
        const createForm = document.querySelector('#create-form');
        const createButton = document.querySelector('#create-button');
        const titleInput = document.querySelector('#title');
        const descriptionInput = document.querySelector('#description');
        const errorBox = document.querySelector('#error-box');
        const loadingState = document.querySelector('#loading-state');
        const summary = document.querySelector('#summary');
        const filterButtons = document.querySelectorAll('.filter-button');
        const importButton = document.querySelector('#import-button');
        const importStatus = document.querySelector('#import-status');
        let importPollId = null;

        function setLoading(message) {
            loadingState.textContent = message;
        }

        function setError(message = '') {
            errorBox.textContent = message;
            errorBox.classList.toggle('visible', Boolean(message));
        }

        async function apiRequest(url, options = {}) {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(options.headers || {}),
                },
                ...options,
            });

            if (response.status === 204) {
                return null;
            }

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Request failed.');
            }

            return payload.data ?? payload;
        }

        function visibleTodos() {
            if (state.filter === 'active') {
                return state.todos.filter((todo) => !todo.is_completed);
            }

            if (state.filter === 'completed') {
                return state.todos.filter((todo) => todo.is_completed);
            }

            return state.todos;
        }

        function renderSummary() {
            const total = state.todos.length;
            const completed = state.todos.filter((todo) => todo.is_completed).length;
            summary.textContent = `${total} total, ${completed} completed`;
        }

        function renderImportStatus() {
            const currentImport = state.import;

            if (currentImport.status === 'idle') {
                importStatus.textContent = 'No import started.';
                importButton.disabled = false;
                return;
            }

            if (currentImport.status === 'pending' || currentImport.status === 'running') {
                importStatus.textContent = `Import #${currentImport.id}: ${currentImport.status}`;
                importButton.disabled = true;
                return;
            }

            if (currentImport.status === 'completed') {
                importStatus.textContent = `Import #${currentImport.id}: completed, ${currentImport.importedCount} todos processed.`;
                importButton.disabled = false;
                return;
            }

            if (currentImport.status === 'failed') {
                importStatus.textContent = `Import #${currentImport.id}: failed. ${currentImport.errorMessage || ''}`;
                importButton.disabled = false;
            }
        }

        function renderTodos() {
            renderSummary();
            const todos = visibleTodos();

            if (todos.length === 0) {
                listEl.innerHTML = '<div class="empty">No todos in this view.</div>';
                return;
            }

            listEl.innerHTML = todos.map((todo) => {
                if (state.editingId === todo.id) {
                    return `
                        <article class="todo-row${todo.is_completed ? ' completed' : ''}" data-id="${todo.id}">
                            <input class="todo-check" type="checkbox" ${todo.is_completed ? 'checked' : ''} data-action="toggle">
                            <form class="edit-form" data-action="save-edit">
                                <div class="field">
                                    <label for="edit-title-${todo.id}">Title</label>
                                    <input id="edit-title-${todo.id}" name="title" type="text" maxlength="255" required value="${escapeHtml(todo.title)}">
                                </div>
                                <div class="field">
                                    <label for="edit-description-${todo.id}">Description</label>
                                    <textarea id="edit-description-${todo.id}" name="description">${escapeHtml(todo.description || '')}</textarea>
                                </div>
                                <div class="edit-actions">
                                    <button class="primary-button" type="submit">Save</button>
                                    <button class="secondary-button" type="button" data-action="cancel-edit">Cancel</button>
                                </div>
                            </form>
                            <div></div>
                        </article>
                    `;
                }

                return `
                    <article class="todo-row${todo.is_completed ? ' completed' : ''}" data-id="${todo.id}">
                        <input class="todo-check" type="checkbox" ${todo.is_completed ? 'checked' : ''} data-action="toggle">
                        <div>
                            <h2 class="todo-title">${escapeHtml(todo.title)}</h2>
                            ${todo.description ? `<p class="todo-description">${escapeHtml(todo.description)}</p>` : ''}
                        </div>
                        <div class="actions">
                            <button class="secondary-button" type="button" data-action="edit">Edit</button>
                            <button class="danger-button" type="button" data-action="delete">Delete</button>
                        </div>
                    </article>
                `;
            }).join('');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        async function loadTodos() {
            setError();
            setLoading('Loading...');

            try {
                state.todos = await apiRequest(apiBase);
                renderTodos();
                setLoading('Ready');
            } catch (error) {
                setError(error.message);
                setLoading('Failed to load');
            }
        }

        async function startDummyJsonImport() {
            setError();
            setLoading('Starting import...');
            importButton.disabled = true;

            try {
                const importRecord = await apiRequest(importApiBase, {
                    method: 'POST',
                });

                state.import = {
                    id: importRecord.id,
                    status: importRecord.status,
                    importedCount: importRecord.imported_count || 0,
                    errorMessage: importRecord.error_message || null,
                };
                renderImportStatus();
                setLoading('Import queued');
                startImportPolling(importRecord.id);
            } catch (error) {
                setError(error.message);
                setLoading('Import failed');
                importButton.disabled = false;
            }
        }

        function startImportPolling(importId) {
            if (importPollId) {
                clearInterval(importPollId);
            }

            importPollId = setInterval(() => {
                pollImportStatus(importId);
            }, 1000);

            pollImportStatus(importId);
        }

        async function pollImportStatus(importId) {
            try {
                const importRecord = await apiRequest(`${importApiBase}s/${importId}`);

                state.import = {
                    id: importRecord.id,
                    status: importRecord.status,
                    importedCount: importRecord.imported_count || 0,
                    errorMessage: importRecord.error_message || null,
                };
                renderImportStatus();

                if (importRecord.status === 'completed') {
                    clearInterval(importPollId);
                    importPollId = null;
                    setLoading('Import completed');
                    await loadTodos();
                }

                if (importRecord.status === 'failed') {
                    clearInterval(importPollId);
                    importPollId = null;
                    setLoading('Import failed');
                }
            } catch (error) {
                clearInterval(importPollId);
                importPollId = null;
                setError(error.message);
                setLoading('Import status failed');
                importButton.disabled = false;
            }
        }

        createForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            setError();
            createButton.disabled = true;
            setLoading('Creating...');

            try {
                const todo = await apiRequest(apiBase, {
                    method: 'POST',
                    body: JSON.stringify({
                        title: titleInput.value.trim(),
                        description: descriptionInput.value.trim() || null,
                    }),
                });

                state.todos = [todo, ...state.todos];
                createForm.reset();
                renderTodos();
                setLoading('Created');
            } catch (error) {
                setError(error.message);
                setLoading('Create failed');
            } finally {
                createButton.disabled = false;
            }
        });

        listEl.addEventListener('click', async (event) => {
            const target = event.target;
            const row = target.closest('.todo-row');

            if (!row) {
                return;
            }

            const id = Number(row.dataset.id);
            const todo = state.todos.find((item) => item.id === id);

            if (!todo) {
                return;
            }

            if (target.dataset.action === 'edit') {
                state.editingId = id;
                renderTodos();
                return;
            }

            if (target.dataset.action === 'cancel-edit') {
                state.editingId = null;
                renderTodos();
                return;
            }

            if (target.dataset.action === 'delete') {
                await deleteTodo(id);
            }
        });

        listEl.addEventListener('change', async (event) => {
            const target = event.target;

            if (target.dataset.action !== 'toggle') {
                return;
            }

            const row = target.closest('.todo-row');
            await updateTodo(Number(row.dataset.id), {
                is_completed: target.checked,
            });
        });

        listEl.addEventListener('submit', async (event) => {
            if (event.target.dataset.action !== 'save-edit') {
                return;
            }

            event.preventDefault();
            const row = event.target.closest('.todo-row');
            const formData = new FormData(event.target);
            state.editingId = null;

            await updateTodo(Number(row.dataset.id), {
                title: formData.get('title').trim(),
                description: formData.get('description').trim() || null,
            });
        });

        async function updateTodo(id, data) {
            setError();
            setLoading('Updating...');

            try {
                const updatedTodo = await apiRequest(`${apiBase}/${id}`, {
                    method: 'PATCH',
                    body: JSON.stringify(data),
                });

                state.todos = state.todos.map((todo) => todo.id === id ? updatedTodo : todo);
                renderTodos();
                setLoading('Updated');
            } catch (error) {
                setError(error.message);
                setLoading('Update failed');
                await loadTodos();
            }
        }

        async function deleteTodo(id) {
            setError();
            setLoading('Deleting...');

            try {
                await apiRequest(`${apiBase}/${id}`, {
                    method: 'DELETE',
                });

                state.todos = state.todos.filter((todo) => todo.id !== id);
                renderTodos();
                setLoading('Deleted');
            } catch (error) {
                setError(error.message);
                setLoading('Delete failed');
            }
        }

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                state.filter = button.dataset.filter;
                filterButtons.forEach((item) => item.classList.toggle('active', item === button));
                renderTodos();
            });
        });

        importButton.addEventListener('click', startDummyJsonImport);

        renderImportStatus();
        loadTodos();
    </script>
</body>
</html>
