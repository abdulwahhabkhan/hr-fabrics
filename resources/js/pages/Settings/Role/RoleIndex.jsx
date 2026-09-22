import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Icon } from '@iconify/react';
import Pagination from '@/components/Pagination';
import SearchFilter from '@/components/SearchFilter';
import { Delete, InertiaEdit } from '@/components/Actions';
import NoData from '@/components/NoData.jsx';

const Roles = () => {
    const { roles } = usePage().props;
    const { data, links } = roles;

    return (
        <>
            <Head title="Roles List" />
            <PageHeader title="Roles List" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={"Roles List"} buttons={(
                        <>
                            <InertiaLink href={route("settings.roles.create")} className="btn btn-xs  btn-primary">
                                <Icon icon={"solar:add-bold-duotone"} /> Create Role
                            </InertiaLink>
                        </>
                    )} />
                    <PanelBody>
                        <SearchFilter />
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered"}>
                                <thead>
                                <tr>
                                    <th className="w-1">Sr</th>
                                    <th>Role Name</th>
                                    <th className="w-1">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                {data.map(({ id, name, updated_at }, index) => {
                                    return (
                                        <tr key={id}>
                                            <td>{index + 1}</td>
                                            <td>{name}</td>

                                            <td className={"actions"}>
                                                <InertiaEdit href={route("settings.roles.edit", id)} />
                                                <Delete id={id} />
                                            </td>
                                        </tr>
                                    );
                                })}
                                </tbody>
                            </table>
                            {data.length === 0 && (<NoData label="No roles found." />)}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Roles;
