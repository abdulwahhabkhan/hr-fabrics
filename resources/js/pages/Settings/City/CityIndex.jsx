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
import { Delete, InertiaEdit } from '@/components/Actions';
import NoData from '@/components/NoData.jsx';
import citiesRoutes from '@/routes/settings/cities';

const Cities = () => {
    const { cities } = usePage().props;
    const { data, links } = cities;

    return (
        <>
            <Head title="Cities List" />
            <PageHeader title="Cities List" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={"Users List"} buttons={(
                        <>
                            <InertiaLink href={citiesRoutes.create()} className="btn btn-xs  btn-primary">
                                <Icon icon={"solar:add-bold-duotone"} /> Create City
                            </InertiaLink>
                        </>
                    )} />
                    <PanelBody>
                        <SearchFilter />
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered"}>
                                <thead>
                                <tr>
                                    <th width={"40"}>Sr</th>
                                    <th>City Name</th>
                                    <th width={"110px"}>Last Modified</th>
                                    <th width={"70"}>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                {data.map(({ id, name, name_urdu, email, role, updated_at }, index) => {
                                    return (
                                        <tr key={id}>
                                            <td>{index + 1}</td>
                                            <td>{name} <span className="urdu p-l-10">{name_urdu}</span></td>
                                            <td>
                                                <Moment
                                                    format={settings.DATE_FORMAT}
                                                    date={updated_at} />
                                            </td>
                                            <td className={"actions"}>
                                                <InertiaEdit href={citiesRoutes.edit(id)} />
                                                <Delete action={citiesRoutes.destroy} id={id} />
                                            </td>
                                        </tr>
                                    );
                                })}
                                </tbody>
                            </table>
                            {data.length === 0 && (<NoData label="No cities found." />)}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Cities;
