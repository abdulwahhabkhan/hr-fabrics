import * as React from 'react';
import { useState } from 'react';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Icon } from '@iconify/react';
import { OverlayTrigger, Tooltip } from 'react-bootstrap';

const humanizeKey = (key) => {
    return key
        .replace(/[._]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

export const ErrorPanel = ({ errors }) => {
    const [collapsed, setCollapsed] = useState(false);
    if (_.isEmpty(errors))
        return <></>;

    const errorEntries = Object.entries(errors);

    return (
        <Panel theme={'danger'} className={'mb-3'}>
            <div
                className="panel-heading bg-danger d-flex align-items-center"
                onClick={() => setCollapsed(!collapsed)}
                style={{ cursor: 'pointer' }}
            >
                <Icon icon={'solar:danger-triangle-bold-duotone'} className={'me-2'} width={20} />
                <h4 className="panel-title mb-0 flex-grow-1">
                    {errorEntries.length === 1
                        ? 'Please fix the following before continuing'
                        : `Please fix the following ${errorEntries.length} issues before continuing`}
                </h4>
                <div className="panel-heading-btn">
                    <OverlayTrigger
                        placement={'bottom-end'}
                        overlay={<Tooltip>{collapsed ? 'Expand' : 'Collapse'}</Tooltip>}
                    >
                        <button
                            type={'button'}
                            className="btn btn-xs btn-icon btn-circle btn-warning"
                            onClick={(e) => {
                                e.stopPropagation();
                                setCollapsed(!collapsed);
                            }}
                        >
                            <Icon icon={collapsed ? 'solar:alt-arrow-down-bold-duotone' : 'solar:alt-arrow-up-bold-duotone'} />
                        </button>
                    </OverlayTrigger>
                </div>
            </div>
            <PanelBody className={collapsed ? 'd-none' : ''}>
                <ul className="list-unstyled mb-0">
                    {errorEntries.map(([key, error], index) => (
                        <li
                            key={key}
                            className={
                                'd-flex align-items-start py-2' +
                                (index < errorEntries.length - 1 ? ' border-bottom' : '')
                            }
                        >
                            <Icon
                                icon={'solar:close-circle-bold-duotone'}
                                className={'text-danger me-2 mt-1 flex-shrink-0'}
                                width={16}
                            />
                            <span>
                                <strong>{humanizeKey(key)}:</strong> {error}
                            </span>
                        </li>
                    ))}
                </ul>
            </PanelBody>
        </Panel>
    );
};

export const updateErrors = (errorList, setError) => {
    Object.entries(errorList).map(([key, error]) => {
        setError(key, {
            type: "manual",
            message: error,
        })
    })
}
