import React from 'react';

// Account types come from the server (accountTypes), so map by keyword and
// fall back to a stable hashed tone for anything unexpected.
const KEYWORD_TONES = [
    [/bank|cash|asset/i, 'navy'],
    [/income|revenue|sale/i, 'green'],
    [/expense|cost/i, 'gold'],
    [/liabilit|payable|loan/i, 'red'],
    [/capital|equity|owner/i, 'plum'],
    [/customer|receivable|client/i, 'teal'],
    [/supplier|vendor/i, 'olive'],
    [/agent|employee|staff/i, 'slate'],
];
const FALLBACK = ['navy', 'green', 'gold', 'plum', 'teal', 'olive', 'slate'];

export const toneForType = (type = '') => {
    const match = KEYWORD_TONES.find(([pattern]) => pattern.test(type));
    if (match) {
        return match[1];
    }

    let hash = 0;
    for (let i = 0; i < type.length; i += 1) {
        hash = (hash * 31 + type.charCodeAt(i)) | 0;
    }
    return FALLBACK[Math.abs(hash) % FALLBACK.length];
};

export default function AccountTypeBadge({ type }) {
    if (!type) {
        return <span className="hf-muted-value">—</span>;
    }

    return <span className={`hf-pill tone-${toneForType(type)}`}>{type.replace(/[_-]+/g, ' ')}</span>;
}
