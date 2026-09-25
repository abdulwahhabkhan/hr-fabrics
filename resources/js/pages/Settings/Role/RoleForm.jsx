import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelFooter, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Form, Row } from 'react-bootstrap';
import LoadingButton from '@/components/LoadingButton';
import { useForm } from 'react-hook-form';
import CheckboxTreeComponent from 'checkbox-tree-react-19';
import PerfectScrollbarComponent from 'react-perfect-scrollbar';
import 'checkbox-tree-react-19/lib/react-checkbox-tree.css';
import 'react-perfect-scrollbar/dist/css/styles.css';
import roles from '@/routes/settings/roles';

let CheckboxTree = CheckboxTreeComponent;
if (CheckboxTreeComponent && CheckboxTreeComponent.default) {
    CheckboxTree = CheckboxTreeComponent.default;
}
// Double check if CheckboxTree is still the module object (Vite/ESM quirk)
if (CheckboxTree && CheckboxTree.default && typeof CheckboxTree.default === "function") {
    CheckboxTree = CheckboxTree.default;
}

let PerfectScrollbar = PerfectScrollbarComponent;
if (PerfectScrollbarComponent && PerfectScrollbarComponent.default) {
    PerfectScrollbar = PerfectScrollbarComponent.default;
}
if (PerfectScrollbar && PerfectScrollbar.default && typeof PerfectScrollbar.default === "function") {
    PerfectScrollbar = PerfectScrollbar.default;
}

function expandNodesToLevel(nodes, targetLevel, currentLevel = 0) {
    if (currentLevel > targetLevel) {
        return [];
    }

    let expanded = [];
    nodes.forEach((node) => {
        if (node.children) {
            expanded = [...expanded, node.value, ...expandNodesToLevel(node.children, targetLevel, currentLevel + 1)];
        }
    });
    return expanded;
}

const RoleForm = () => {
    const { role, permissions, rolePermission } = usePage().props;
    const title = role ? "Edit Role" : "Add Role";
    const [processing, setProcessing] = useState(false);
    const [checked, setChecked] = useState(rolePermission);
    const [expanded, setExpanded] = useState(expandNodesToLevel(permissions, 2));
    const defaultValues = role;
    const {
        register,
        handleSubmit,
        formState: { errors }
    } = useForm({ defaultValues: defaultValues });
    const sendRequest = async (data) => {
        setProcessing(true);
        if (role) Inertia.put(roles.update(role["id"]), { ...data, permissions: { ...checked } });
        else Inertia.post(roles.store(), { ...data, permissions: { ...checked } });
    };
    const onExpand = (expanded) => {
        setExpanded(expanded);
    };
    const onCheck = (val) => {
        setChecked(val);
    };
    return (
        <>
            <Head title="Roles Update" />
            <PageHeader title="Roles Update" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={title} buttons={(
                        <>
                            <InertiaLink href={roles.index()} className="btn btn-xs  btn-primary">
                                <Icon icon={"solar:reply-bold-duotone"} /> Roles List
                            </InertiaLink>
                        </>
                    )} />
                    <PanelBody>
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Name:</Form.Label>
                                        <Form.Control
                                            {...register("name", { required: true })}
                                            isInvalid={errors.name}
                                            placeholder={"role name"}
                                        />
                                    </Form.Group>
                                </Col>
                                <Col sm={6}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Description:</Form.Label>
                                        <Form.Control
                                            {...register("description")}
                                            isInvalid={errors.description}
                                            placeholder={"description"}
                                        />
                                    </Form.Group>
                                </Col>
                            </Row>

                            <Panel theme={"default"}>
                                <PanelHeader>Role Permissions</PanelHeader>
                                <PanelBody>
                                    <CheckboxTree
                                        nodes={permissions}
                                        checked={checked}
                                        expanded={expanded}
                                        icons={{
                                            check: (
                                                <Icon className="rct-icon rct-icon-check" icon={"solar:check-square-bold-duotone"} />
                                            ),
                                            uncheck: (
                                                <Icon className="rct-icon rct-icon-uncheck" icon={"solar:stop-bold-duotone"} />
                                            ),
                                            halfCheck: (
                                                <Icon
                                                    className="rct-icon rct-icon-half-check"
                                                    icon={"solar:check-square-bold-duotone"}
                                                />
                                            ),
                                            expandClose: (
                                                <Icon
                                                    className="rct-icon rct-icon-expand-close"
                                                    icon={"solar:alt-arrow-right-bold-duotone"}
                                                />
                                            ),
                                            expandOpen: (
                                                <Icon
                                                    className="rct-icon rct-icon-expand-open"
                                                    icon={"solar:alt-arrow-down-bold-duotone"}
                                                />
                                            ),
                                            expandAll: (
                                                <Icon
                                                    className="rct-icon rct-icon-expand-all"
                                                    icon={"solar:add-square-bold-duotone"}
                                                />
                                            ),
                                            collapseAll: (
                                                <Icon
                                                    className="rct-icon rct-icon-collapse-all"
                                                    icon={"solar:minus-square-bold-duotone"}
                                                />
                                            ),
                                            parentClose: (
                                                <Icon
                                                    className="rct-icon rct-icon-parent-close"
                                                    icon={"solar:folder-bold-duotone"}
                                                />
                                            ),
                                            parentOpen: (
                                                <Icon
                                                    className="rct-icon rct-icon-parent-open"
                                                    icon={"solar:folder-open-bold-duotone"}
                                                />
                                            ),
                                            leaf: (
                                                <Icon className="rct-icon rct-icon-leaf-close" icon={"solar:file-bold-duotone"} />
                                            )
                                        }}
                                        showExpandAll
                                        onCheck={onCheck}
                                        onExpand={onExpand}
                                    />
                                </PanelBody>
                            </Panel>
                        </form>
                    </PanelBody>
                    <PanelFooter className={"text-center"}>
                        <InertiaLink href={roles.index()} className="btn btn-white">
                            <Icon icon={"solar:reply-bold-duotone"} />
                        </InertiaLink>

                        <LoadingButton processing={processing} onClick={handleSubmit(sendRequest)}>
                            Save Changes
                        </LoadingButton>
                    </PanelFooter>
                </Panel>
            </PageContent>
        </>
    );
};

export default RoleForm;
