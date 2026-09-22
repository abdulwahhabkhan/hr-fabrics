import React from 'react';
import cx from 'classnames';

const PanelStat = React.createContext();

class Panel extends React.Component {
    constructor(props) {
        super(props);

        this.toggleExpand = () => {
            this.setState(state => ({
                expand: !this.state.expand
            }));
        }

        this.toggleRemove = () => {
            this.setState(state => ({
                remove: !this.state.remove
            }));
        }

        this.toggleCollapse = () => {
            this.setState(state => ({
                collapse: !this.state.collapse
            }));
        }

        this.toggleReload = () => {
            if (this.state.reload !== true) {
                this.setState(state => ({
                    reload: true
                }));

                setTimeout(() => {
                    this.setState(state => ({
                        reload: false
                    }));
                }, 2000);
            }
        }

        this.state = {
            expand: false,
            collapse: false,
            reload: false,
            remove: false,
            toggleExpand: this.toggleExpand,
            toggleReload: this.toggleReload,
            toggleRemove: this.toggleRemove,
            toggleCollapse: this.toggleCollapse
        }
    }

    render() {
        return (
            <PanelStat.Provider value={this.state}>
                {(!this.state.remove &&
                    <div
                        className={cx(
                            'panel',
                            `panel-${this.props.theme || 'default'}`,
                            {
                                'panel-expand': this.state.expand,
                                'panel-loading': this.state.reload
                            },
                            this.props.className
                        )}>
                        {this.props.children}
                    </div>
                )}
            </PanelStat.Provider>
        );
    }
}

function PanelHeader({ heading, buttons, className, children }) {
    if (!heading && !buttons) {
        return (
            <div className={cx('panel-heading', className)}>
                <h4 className="panel-title">
                    <div className="d-md-flex align-items-center justify-content-between">
                        {children}
                    </div>
                </h4>
            </div>
        )
    }

    return (
        <div className={cx('panel-heading', className)}>
            <h4 className="panel-title">{heading}</h4>
            {buttons && <div className="panel-heading-btn gap-2 align-items-center">{buttons}</div>}
        </div>
    )
}

function PanelBody(props) {

    return (
        <PanelStat.Consumer>
            {({collapse, reload}) => (
                <div className={cx('panel-body', { 'd-none': collapse }, props.className)}>
                    {props.children}

                    {(reload &&
                        <div className="panel-loader">
                            <span className="spinner-small"></span>
                        </div>
                    )}
                </div>
            )}
        </PanelStat.Consumer>
    );

}

function PanelFooter(props) {
    return <div className={cx('panel-footer', props.className)}>{props.children}</div>;
}

export {Panel, PanelHeader, PanelBody, PanelFooter}
