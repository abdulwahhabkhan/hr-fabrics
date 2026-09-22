import * as React from 'react';
import {Badge, OverlayTrigger, Tooltip} from "react-bootstrap";
import {NumberFormat} from "@/util/NumberFormat";
import { Icon } from "@iconify/react";


export const PriceView = ({unit, suit}) => {
    return (
        <div className={'price-box align-items-center'}>

            {
                suit > 0 && (
                    <>
                        <Badge bg={'inverse'} className={'price'}>
                            <OverlayTrigger
                                placement={'bottom'}
                                overlay={
                                    <Tooltip>Suit Price</Tooltip>
                                }
                            >
                                <span>
                                    <Icon icon={"solar:user-rounded-bold-duotone"}/> &nbsp;
                                    <NumberFormat displayType={'text'}
                                                  value={suit} thousandSeparator={true}/>
                                </span>
                            </OverlayTrigger>
                        </Badge>
                        &nbsp;
                    </>
                )
            }
            {
                suit < 1 && (
                    <label htmlFor="">&nbsp;</label>
                )
            }
            <Badge bg={"success"} className="price">
                <OverlayTrigger
                    placement={'bottom'}
                    overlay={
                        <Tooltip>Unit Price</Tooltip>
                    }
                >
                    <span>
                        <Icon icon={suit ? "solar:ruler-bold-duotone" : "solar:t-shirt-bold-duotone"}/> &nbsp;
                        <NumberFormat displayType={'text'}
                                      value={unit} thousandSeparator={true}/>
                    </span>

                </OverlayTrigger>
            </Badge>

        </div>
    );
};
