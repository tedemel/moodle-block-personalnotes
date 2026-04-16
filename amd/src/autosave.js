// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Personal Notes block — tab management + auto-save.
 *
 * @module     block_personalnotes/autosave
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_strings as getStrings} from 'core/str';

const DEBOUNCE_MS = 600;

/**
 * Strip browser-injected inline styles / empty spans before saving.
 * execCommand adds Bootstrap CSS-var style attrs that PARAM_CLEANHTML rejects.
 *
 * @param {HTMLElement} el
 * @returns {string}
 */
const getCleanHtml = (el) => {
    const clone = el.cloneNode(true);
    clone.querySelectorAll('[style]').forEach(n => n.removeAttribute('style'));
    clone.querySelectorAll('span').forEach(span => {
        if (span.attributes.length === 0) {
            while (span.firstChild) {
                span.parentNode.insertBefore(span.firstChild, span);
            }
            span.parentNode.removeChild(span);
        }
    });
    return clone.innerHTML;
};

/**
 * Initialise the tabs block for a given context.
 *
 * @param {number} contextId
 */
export const init = async(contextId) => {
    const root = document.querySelector('[data-personalnotes-context="' + contextId + '"]');
    if (!root) {
        return;
    }

    const statusEl = root.querySelector('[data-personalnotes-status]');

    const [savedStr, savingStr, confirmDeleteStr, deleteTabStr] = await getStrings([
        {key: 'saved',         component: 'block_personalnotes'},
        {key: 'saving',        component: 'block_personalnotes'},
        {key: 'confirmdelete', component: 'block_personalnotes'},
        {key: 'deletetab',     component: 'block_personalnotes'},
    ]);

    // ── Helpers ────────────────────────────────────────────────────────────

    const showStatus = (msg) => {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = msg;
        if (msg) {
            setTimeout(() => { statusEl.textContent = ''; }, 2000);
        }
    };

    /** Return the currently active editor pane. */
    const activeEditor = () => root.querySelector('.personalnotes-editor.active');

    /** Return the currently active tab button. */
    const activeTabBtn = () => root.querySelector('.personalnotes-tab.active');

    // ── Auto-save ──────────────────────────────────────────────────────────

    let saveTimer = null;

    const schedSave = (editor) => {
        statusEl && (statusEl.textContent = savingStr);
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            const tabId = parseInt(editor.dataset.tabid, 10);
            Ajax.call([{
                methodname: 'block_personalnotes_save_note',
                args: {tabid: tabId, notetext: getCleanHtml(editor)},
            }])[0]
            .then(() => { showStatus(savedStr); return true; })
            .catch(Notification.exception);
        }, DEBOUNCE_MS);
    };

    // Attach input listener to all existing editors.
    root.querySelectorAll('.personalnotes-editor').forEach(ed => {
        ed.addEventListener('input', () => schedSave(ed));
    });

    // ── Toolbar ────────────────────────────────────────────────────────────

    root.addEventListener('mousedown', (e) => {
        const btn = e.target.closest('[data-cmd]');
        if (!btn) {
            return;
        }
        e.preventDefault();
        document.execCommand(btn.dataset.cmd, false, null);
        const ed = activeEditor();
        if (ed) {
            ed.focus();
            schedSave(ed);
        }
    });

    // ── Tab switching ──────────────────────────────────────────────────────

    root.addEventListener('click', (e) => {
        const tabBtn = e.target.closest('.personalnotes-tab');
        if (!tabBtn || e.target.closest('[data-action]')) {
            return; // ignore clicks on action buttons inside the tab
        }
        if (tabBtn.classList.contains('active')) {
            return;
        }

        // Deactivate current.
        root.querySelectorAll('.personalnotes-tab').forEach(t => t.classList.remove('active'));
        root.querySelectorAll('.personalnotes-editor').forEach(ed => {
            ed.classList.remove('active');
            ed.style.display = 'none';
        });

        // Activate clicked.
        tabBtn.classList.add('active');
        const tabId  = tabBtn.dataset.tabid;
        const editor = root.querySelector('.personalnotes-editor[data-tabid="' + tabId + '"]');
        if (editor) {
            editor.classList.add('active');
            editor.style.display = '';
            editor.focus();
        }
    });

    // ── Add tab (+ button) ─────────────────────────────────────────────────

    root.querySelector('[data-action="addtab"]')?.addEventListener('click', () => {
        Ajax.call([{
            methodname: 'block_personalnotes_create_tab',
            args: {contextid: contextId, tabname: ''},  // server generates numbered name
        }])[0]
        .then((result) => {
            appendTab(result.id, result.tabname, true);
            return result;
        })
        .catch(Notification.exception);
    });

    // ── Rename tab (double-click on label) ─────────────────────────────────

    root.addEventListener('dblclick', (e) => {
        const label = e.target.closest('.personalnotes-tablabel');
        if (!label) {
            return;
        }
        const tabBtn = label.closest('.personalnotes-tab');
        const tabId  = parseInt(tabBtn.dataset.tabid, 10);
        const input  = document.createElement('input');
        input.type  = 'text';
        input.value = label.textContent.trim();
        input.className = 'personalnotes-tabrename form-control form-control-sm';
        input.style.cssText = 'width:90px;display:inline-block;padding:1px 4px;height:auto;';

        label.replaceWith(input);
        input.select();

        const commit = () => {
            const name = input.value.trim() || label.textContent.trim();
            const newLabel = document.createElement('span');
            newLabel.className   = 'personalnotes-tablabel';
            newLabel.textContent = name;
            input.replaceWith(newLabel);

            Ajax.call([{
                methodname: 'block_personalnotes_rename_tab',
                args: {tabid: tabId, tabname: name},
            }])[0]
            .then((res) => { newLabel.textContent = res.tabname; return res; })
            .catch(Notification.exception);
        };

        input.addEventListener('blur', commit);
        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
            if (ev.key === 'Escape') { input.value = label.textContent; input.blur(); }
        });
    });

    // ── Delete tab (× button) ──────────────────────────────────────────────

    root.addEventListener('click', (e) => {
        const delBtn = e.target.closest('[data-action="deletetab"]');
        if (!delBtn) {
            return;
        }
        e.stopPropagation();
        if (!window.confirm(confirmDeleteStr)) {
            return;
        }
        const tabBtn = delBtn.closest('.personalnotes-tab');
        const tabId  = parseInt(tabBtn.dataset.tabid, 10);

        Ajax.call([{
            methodname: 'block_personalnotes_delete_tab',
            args: {tabid: tabId},
        }])[0]
        .then((res) => {
            if (!res.success) {
                return res;
            }
            const wasActive = tabBtn.classList.contains('active');
            const editor    = root.querySelector('.personalnotes-editor[data-tabid="' + tabId + '"]');

            tabBtn.remove();
            editor?.remove();

            // Activate first remaining tab if we deleted the active one.
            if (wasActive) {
                const firstTab = root.querySelector('.personalnotes-tab');
                if (firstTab) {
                    firstTab.click();
                }
            }

            // Hide delete buttons if only one tab left.
            updateDeleteVisibility();
            return res;
        })
        .catch(Notification.exception);
    });

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Add a new tab + editor to the DOM and optionally activate it. */
    const appendTab = (tabId, tabName, activate = false) => {
        // Deactivate others if activating new.
        if (activate) {
            root.querySelectorAll('.personalnotes-tab').forEach(t => t.classList.remove('active'));
            root.querySelectorAll('.personalnotes-editor').forEach(ed => {
                ed.classList.remove('active');
                ed.style.display = 'none';
            });
        }

        // Build tab button.
        const tabBtn = document.createElement('button');
        tabBtn.type = 'button';
        tabBtn.className = 'personalnotes-tab btn btn-sm' + (activate ? ' active' : '');
        tabBtn.dataset.tabid = tabId;
        tabBtn.innerHTML =
            '<span class="personalnotes-tablabel">' + escHtml(tabName) + '</span>'
            + '<span data-action="deletetab" class="personalnotes-tabdelete ms-1" '
            + 'title="' + escHtml(deleteTabStr) + '" aria-label="' + escHtml(deleteTabStr) + '">&times;</span>';

        root.querySelector('[data-personalnotes-tabs]').insertBefore(
            tabBtn,
            root.querySelector('[data-action="addtab"]')
        );

        // Build editor pane.
        const editor = document.createElement('div');
        editor.className = 'form-control personalnotes-editor' + (activate ? ' active' : '');
        editor.contentEditable = 'true';
        editor.dataset.tabid   = tabId;
        editor.style.cssText   = 'min-height:90px;overflow-y:auto;white-space:pre-wrap;'
            + (activate ? '' : 'display:none;');
        editor.setAttribute('aria-multiline', 'true');
        editor.addEventListener('input', () => schedSave(editor));

        root.querySelector('[data-personalnotes-editors]').appendChild(editor);
        if (activate) {
            editor.focus();
        }

        updateDeleteVisibility();
    };

    /** Show/hide × buttons depending on tab count. */
    const updateDeleteVisibility = () => {
        const tabs    = root.querySelectorAll('.personalnotes-tab');
        const show    = tabs.length > 1;
        tabs.forEach(t => {
            const del = t.querySelector('[data-action="deletetab"]');
            if (del) {
                del.style.display = show ? '' : 'none';
            }
        });
    };

    const escHtml = (str) => str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    // Initial delete-button visibility.
    updateDeleteVisibility();
};
