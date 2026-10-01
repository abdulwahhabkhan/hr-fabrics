import React, { useEffect, useMemo, useRef, useState } from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';

/** Checkbox that can render the indeterminate (partially selected) state. */
function TriStateCheckbox({ checked, indeterminate, onChange, label }) {
    const inputRef = useRef(null);

    useEffect(() => {
        if (inputRef.current) {
            inputRef.current.indeterminate = indeterminate;
        }
    }, [indeterminate]);

    return (
        <input
            ref={inputRef}
            type="checkbox"
            className="form-check-input"
            checked={checked}
            onChange={onChange}
            aria-label={label}
            onClick={(event) => event.stopPropagation()}
        />
    );
}

/** Strip the words the sibling labels share, leaving the action ("Catalog Brands Index" -> "Index"). */
function actionLabels(permissions) {
    const words = permissions.map((permission) => permission.label.split(' '));
    if (words.length < 2) {
        return permissions.map((permission) => permission.label.split(' ').slice(-1).join(' '));
    }

    let shared = 0;
    while (words.every((parts) => parts.length > shared + 1 && parts[shared] === words[0][shared])) {
        shared++;
    }

    return words.map((parts) => parts.slice(shared).join(' '));
}

function selectionState(ids, selected) {
    const count = ids.filter((id) => selected.has(id)).length;

    return { count, total: ids.length, all: count > 0 && count === ids.length, some: count > 0 && count < ids.length };
}

/**
 * Module → section → permission picker. Works on the nested tree from `Permission::listPermissions()`
 * and reports the selected leaf permission ids through `onChange`.
 */
export default function PermissionMatrix({ permissions, value, onChange }) {
    const [search, setSearch] = useState('');
    const [collapsed, setCollapsed] = useState(() => new Set());
    const selected = useMemo(() => new Set(value), [value]);

    const modules = useMemo(
        () =>
            permissions.map((module) => {
                const sections = module.children.map((section) => {
                    const labels = actionLabels(section.children);
                    const items = section.children.map((permission, index) => ({
                        id: permission.value,
                        label: labels[index] || permission.label,
                        fullLabel: permission.label,
                    }));

                    return { key: section.value, label: section.label, items, ids: items.map((item) => item.id) };
                });

                return { key: module.value, label: module.label, sections, ids: sections.flatMap((section) => section.ids) };
            }),
        [permissions],
    );

    const allIds = useMemo(() => modules.flatMap((module) => module.ids), [modules]);
    const term = search.trim().toLowerCase();

    const visibleModules = useMemo(() => {
        if (!term) {
            return modules;
        }

        return modules
            .map((module) => {
                if (module.label.toLowerCase().includes(term)) {
                    return module;
                }

                const sections = module.sections
                    .map((section) => {
                        if (section.label.toLowerCase().includes(term)) {
                            return section;
                        }

                        const items = section.items.filter((item) => item.fullLabel.toLowerCase().includes(term));

                        return items.length ? { ...section, items, ids: items.map((item) => item.id) } : null;
                    })
                    .filter(Boolean);

                return sections.length ? { ...module, sections, ids: sections.flatMap((section) => section.ids) } : null;
            })
            .filter(Boolean);
    }, [modules, term]);

    const setMany = (ids, isSelected) => {
        const next = new Set(selected);
        ids.forEach((id) => (isSelected ? next.add(id) : next.delete(id)));
        onChange(allIds.filter((id) => next.has(id)));
    };

    const toggleGroup = (ids) => setMany(ids, !selectionState(ids, selected).all);

    const toggleCollapsed = (key) => {
        const next = new Set(collapsed);
        next.has(key) ? next.delete(key) : next.add(key);
        setCollapsed(next);
    };

    const visibleIds = visibleModules.flatMap((module) => module.ids);
    const allCollapsed = modules.every((module) => collapsed.has(module.key));

    return (
        <div className="hf-perms">
            <div className="hf-perms__toolbar">
                <div className="hf-perms__search">
                    <Icon icon="solar:magnifer-linear" />
                    <input
                        type="search"
                        className="form-control"
                        placeholder="Search permissions..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </div>
                <div className="hf-perms__bulk">
                    <button type="button" className="btn btn-xs btn-white" onClick={() => setMany(visibleIds, true)}>
                        Select {term ? 'matches' : 'all'}
                    </button>
                    <button type="button" className="btn btn-xs btn-white" onClick={() => setMany(visibleIds, false)}>
                        Clear {term ? 'matches' : 'all'}
                    </button>
                    {!term && (
                        <button
                            type="button"
                            className="btn btn-xs btn-white"
                            onClick={() => setCollapsed(allCollapsed ? new Set() : new Set(modules.map((module) => module.key)))}
                        >
                            <Icon icon={allCollapsed ? 'solar:alt-arrow-down-linear' : 'solar:alt-arrow-up-linear'} />
                            {allCollapsed ? 'Expand all' : 'Collapse all'}
                        </button>
                    )}
                </div>
            </div>

            <div className="hf-perms__summary">
                <strong>{selected.size}</strong> of {allIds.length} permissions granted
                <span className="hf-perms__meter">
                    <span style={{ width: `${allIds.length ? (selected.size / allIds.length) * 100 : 0}%` }} />
                </span>
            </div>

            {visibleModules.length === 0 && <div className="hf-perms__empty">No permissions match “{search}”.</div>}

            {visibleModules.map((module) => {
                const moduleState = selectionState(module.ids, selected);
                const isOpen = Boolean(term) || !collapsed.has(module.key);

                return (
                    <div key={module.key} className={cx('hf-perms__module', { 'is-open': isOpen })}>
                        <div
                            className="hf-perms__module-head"
                            role="button"
                            tabIndex={0}
                            aria-expanded={isOpen}
                            onClick={() => toggleCollapsed(module.key)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' || event.key === ' ') {
                                    event.preventDefault();
                                    toggleCollapsed(module.key);
                                }
                            }}
                        >
                            <TriStateCheckbox
                                checked={moduleState.all}
                                indeterminate={moduleState.some}
                                onChange={() => toggleGroup(module.ids)}
                                label={`All ${module.label} permissions`}
                            />
                            <span className="hf-perms__module-title">{module.label}</span>
                            <span className={cx('hf-perms__count', { 'is-full': moduleState.all, 'is-partial': moduleState.some })}>
                                {moduleState.count}/{moduleState.total}
                            </span>
                            <Icon icon="solar:alt-arrow-down-linear" className="hf-perms__chevron" />
                        </div>

                        {isOpen && (
                            <div className="hf-perms__sections">
                                {module.sections.map((section) => {
                                    const sectionState = selectionState(section.ids, selected);

                                    return (
                                        <div key={section.key} className="hf-perms__section">
                                            <label className="hf-perms__section-label">
                                                <TriStateCheckbox
                                                    checked={sectionState.all}
                                                    indeterminate={sectionState.some}
                                                    onChange={() => toggleGroup(section.ids)}
                                                    label={`All ${section.label} permissions`}
                                                />
                                                <span>{section.label}</span>
                                            </label>
                                            <div className="hf-perms__actions">
                                                {section.items.map((item) => (
                                                    <label
                                                        key={item.id}
                                                        className={cx('hf-perms__action', { 'is-on': selected.has(item.id) })}
                                                        title={item.fullLabel}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={selected.has(item.id)}
                                                            onChange={() => setMany([item.id], !selected.has(item.id))}
                                                        />
                                                        <Icon icon={selected.has(item.id) ? 'solar:check-circle-bold' : 'solar:add-circle-linear'} />
                                                        {item.label}
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
