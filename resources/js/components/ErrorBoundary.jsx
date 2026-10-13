import React from 'react';
import { Icon } from '@iconify/react';
import { Panel, PanelBody } from '@/components/panel/panel';

/**
 * Catches render errors in its subtree and shows a fallback panel instead of
 * letting React unmount the whole page (blank screen).
 *
 * Pass a changing `resetKey` (e.g. the current URL) to clear the error after
 * navigation.
 */
export default class ErrorBoundary extends React.Component {
    state = { error: null };

    static getDerivedStateFromError(error) {
        return { error };
    }

    componentDidCatch(error, info) {
        console.error(error, info?.componentStack);
    }

    componentDidUpdate(prevProps) {
        if (this.state.error && prevProps.resetKey !== this.props.resetKey) {
            this.setState({ error: null });
        }
    }

    render() {
        const { error } = this.state;

        if (!error) {
            return this.props.children;
        }

        return (
            <Panel theme={'danger'} className={'m-3'}>
                <div className="panel-heading bg-danger d-flex align-items-center">
                    <Icon icon={'solar:danger-triangle-bold-duotone'} className={'me-2'} width={20} />
                    <h4 className="panel-title mb-0 flex-grow-1">Something went wrong while showing this page</h4>
                </div>
                <PanelBody>
                    <p className="mb-2">
                        An unexpected error occurred. You can try again, reload the page, or go back.
                    </p>
                    {import.meta.env.DEV && (
                        <pre className="small text-danger bg-light p-2 rounded mb-3">{String(error?.message ?? error)}</pre>
                    )}
                    <div className="d-flex gap-2">
                        <button type="button" className="btn btn-sm btn-theme" onClick={() => this.setState({ error: null })}>
                            Try again
                        </button>
                        <button type="button" className="btn btn-sm btn-white" onClick={() => window.location.reload()}>
                            Reload page
                        </button>
                        <button type="button" className="btn btn-sm btn-white" onClick={() => window.history.back()}>
                            Go back
                        </button>
                    </div>
                </PanelBody>
            </Panel>
        );
    }
}
