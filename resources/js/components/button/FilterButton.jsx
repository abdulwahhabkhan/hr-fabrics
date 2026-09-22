import { Icon } from "@iconify/react";
import React from "react";
import { Button } from "react-bootstrap";

export default function FilterButton(props) {
    return (
        <Button variant="secondary" {...props}>
            <Icon className={"fs-15px"} icon={"solar:filter-bold-duotone"} />
            &nbsp;Reset
        </Button>
    );
}
