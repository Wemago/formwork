import { clamp } from "../utils/math";

interface TooltipOptions {
    container: HTMLElement;
    referenceElement: HTMLElement;
    position: "top" | "right" | "bottom" | "left" | "center" | { x: number; y: number };
    offset: {
        x: number;
        y: number;
    };
    delay: number;
    timeout: number | null;
    removeOnMouseout: boolean;
    removeOnClick: boolean;
    zIndex: number | null;
}

export class Tooltip {
    text: string;
    options: TooltipOptions;
    delayTimer: number;
    timeoutTimer: number;
    tooltipElement: HTMLElement;

    get removed() {
        return this.tooltipElement === undefined || !this.options.container.contains(this.tooltipElement);
    }

    constructor(text: string, options: Partial<TooltipOptions> = {}) {
        const defaults = {
            container: document.body,
            referenceElement: document.body,
            position: "top",
            offset: {
                x: 0,
                y: 0,
            },
            delay: 500,
            timeout: null,
            removeOnMouseout: true,
            removeOnClick: false,
            zIndex: null,
        };

        this.text = text;
        this.options = Object.assign({}, defaults, options);
    }

    show() {
        const options = this.options;
        const container = options.container;

        this.delayTimer = window.setTimeout(() => {
            const tooltip = document.createElement("div");
            tooltip.className = "tooltip";
            tooltip.setAttribute("role", "tooltip");
            tooltip.style.display = "block";
            tooltip.innerHTML = this.text;

            const getRelativePosition = (tooltip: HTMLElement) => {
                const offset = options.offset;

                const referenceElement = options.referenceElement;

                const rect = referenceElement.getBoundingClientRect();

                const top = rect.top + window.scrollY;
                const left = rect.left + window.scrollX;

                const hw = (rect.width - tooltip.offsetWidth) / 2;
                const hh = (rect.height - tooltip.offsetHeight) / 2;

                switch (options.position) {
                    case "top":
                    default:
                        return {
                            top: Math.round(top - tooltip.offsetHeight + offset.y),
                            left: Math.round(left + hw + offset.x),
                        };
                    case "right":
                        return {
                            top: Math.round(top + hh + offset.y),
                            left: Math.round(left + referenceElement.offsetWidth + offset.x),
                        };
                    case "bottom":
                        return {
                            top: Math.round(top + referenceElement.offsetHeight + offset.y),
                            left: Math.round(left + hw + offset.x),
                        };
                    case "left":
                        return {
                            top: Math.round(top + hh + offset.y),
                            left: Math.round(left - tooltip.offsetWidth + offset.x),
                        };
                    case "center":
                        return {
                            top: Math.round(top + hh + offset.y),
                            left: Math.round(left + hw + offset.x),
                        };
                }
            };

            const getTooltipPosition = (tooltip: HTMLElement) => {
                const position =
                    typeof options.position === "string"
                        ? getRelativePosition(tooltip)
                        : {
                              top: options.position.y + options.offset.y,
                              left: options.position.x + options.offset.x,
                          };

                const min = {
                    top: window.scrollY + 4,
                    left: window.scrollX + 4,
                };

                const max = {
                    top: window.innerHeight + window.scrollY - tooltip.offsetHeight - 20,
                    left: window.innerWidth + window.scrollX - tooltip.offsetWidth - 4,
                };

                return {
                    top: clamp(position.top, min.top, max.top),
                    left: clamp(position.left, min.left, max.left),
                };
            };

            container.appendChild(tooltip);

            const position = getTooltipPosition(tooltip);
            tooltip.style.top = `${position.top}px`;
            tooltip.style.left = `${position.left}px`;

            if (options.zIndex !== null) {
                tooltip.style.zIndex = `${options.zIndex}`;
            }

            if (options.timeout !== null) {
                this.timeoutTimer = window.setTimeout(() => this.remove(), options.timeout);
            }

            if (options.removeOnMouseout) {
                tooltip.addEventListener("mouseout", () => this.remove());
            }

            this.tooltipElement = tooltip;
        }, options.delay);

        const referenceElement = options.referenceElement;

        if (referenceElement.tagName.toLowerCase() === "button" || referenceElement.classList.contains("button")) {
            referenceElement.addEventListener("click", () => {
                this.remove();
            });
            referenceElement.addEventListener("blur", () => this.remove());
        }

        if (options.removeOnMouseout) {
            referenceElement.addEventListener("mouseout", (event: MouseEvent) => {
                if (event.relatedTarget !== this.tooltipElement) {
                    this.remove();
                }
            });
        }
        if (options.removeOnClick) {
            referenceElement.addEventListener("click", () => this.remove());
        }
    }

    remove() {
        clearTimeout(this.delayTimer);
        clearTimeout(this.timeoutTimer);

        const tooltip = this.tooltipElement;
        const container = this.options.container;

        if (tooltip !== undefined && container.contains(tooltip)) {
            container.removeChild(tooltip);
        }
    }
}
