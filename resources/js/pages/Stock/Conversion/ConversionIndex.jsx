import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import Pagination from '@/components/Pagination';
import SearchFilter from '@/components/SearchFilter';
import { Delete } from '@/components/Actions';
import NoData from '@/components/NoData.jsx';

const Coversion = () => {
    const { items } = usePage().props;
    const {
        data,
        meta: { links },
    } = items;

    return (
        <>
            <Head title="Conversion List" />
            <PageHeader title="Conversion List" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={'Conversions List'}
                        buttons={
                            <>
                                <InertiaLink
                                    href={route('stocks.conversions.create')}
                                    className="btn btn-xs  btn-primary"
                                >
                                    <Icon icon={'solar:add-bold-duotone'} />{' '}
                                    Create Conversion
                                </InertiaLink>
                            </>
                        }
                    />
                    <PanelBody>
                        <SearchFilter />
                        <div className={'table-responsive'}>
                            <table className={'table table-bordered'}>
                                <thead>
                                    <tr>
                                        <th width={'40'}>Sr</th>
                                        <th>Product</th>
                                        <th width={'200'}>From</th>
                                        <th width={'200'}>To</th>
                                        <th width={'110px'}>Last Modified</th>
                                        <th width={'70'}>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            { id, sku, from, to, updated_at },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td>{index + 1}</td>
                                                    <td>{sku}</td>
                                                    <td>
                                                        {from.qty +
                                                            ' ' +
                                                            from.unit +
                                                            ' ' +
                                                            from.size}
                                                    </td>
                                                    <td>
                                                        {to.qty +
                                                            ' ' +
                                                            to.unit +
                                                            ' ' +
                                                            to.size}
                                                    </td>
                                                    <td>
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </td>
                                                    <td className={'actions'}>
                                                        <Delete
                                                            action={
                                                                'stocks.conversions.destroy'
                                                            }
                                                            id={id}
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {data.length === 0 && (
                                <NoData label="No conversions found." />
                            )}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Coversion;
