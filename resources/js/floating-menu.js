const GAP = 6;
const PAD = 8;
const FLOAT_HOST_Z = 10040;
const FLOAT_PANEL_Z = 10050;

function pinFloatAnchors(panel) {
    if (panel._floatPinnedHost?.isConnected) {
        const trigger = resolveFloatTrigger(panel._floatPinnedHost);
        if (trigger) {
            panel._floatTrigger = trigger;
        }
        return panel._floatPinnedHost;
    }

    const host = panel.parentElement;
    if (!host || host === document.body) {
        return null;
    }

    panel._floatPinnedHost = host;
    panel._floatHost = host;
    panel._floatTrigger = resolveFloatTrigger(host);

    return host;
}

function resolveFloatTrigger(host) {
    if (!host) {
        return null;
    }

    return host.querySelector('[data-float-trigger]')
        || host.querySelector(':scope > button')
        || host.querySelector('button');
}

function findTrigger(panel) {
    const host = pinFloatAnchors(panel);
    if (!host) {
        return null;
    }

    if (panel._floatTrigger?.isConnected) {
        return panel._floatTrigger;
    }

    panel._floatTrigger = resolveFloatTrigger(host);
    return panel._floatTrigger?.isConnected ? panel._floatTrigger : null;
}

function measurePanel(panel) {
    const previous = {
        position: panel.style.position,
        visibility: panel.style.visibility,
        display: panel.style.display,
        top: panel.style.top,
        left: panel.style.left,
        right: panel.style.right,
        bottom: panel.style.bottom,
        pointerEvents: panel.style.pointerEvents,
    };

    panel.style.position = 'fixed';
    panel.style.visibility = 'hidden';
    panel.style.display = 'block';
    panel.style.pointerEvents = 'none';
    panel.style.top = '0';
    panel.style.left = '0';
    panel.style.right = 'auto';
    panel.style.bottom = 'auto';

    const width = panel.offsetWidth;
    const height = panel.offsetHeight;

    panel.style.position = previous.position;
    panel.style.visibility = previous.visibility;
    panel.style.display = previous.display;
    panel.style.top = previous.top;
    panel.style.left = previous.left;
    panel.style.right = previous.right;
    panel.style.bottom = previous.bottom;
    panel.style.pointerEvents = previous.pointerEvents;

    return { width, height };
}

function clearFloatHostLayer(panel) {
    const host = panel._floatPinnedHost?.isConnected ? panel._floatPinnedHost : null;
    if (!host) {
        return;
    }

    host.classList.remove('nttu-float-host-open');
    host.style.removeProperty('z-index');
}

function clearFloatPosition(panel) {
    clearFloatHostLayer(panel);
    panel.style.position = '';
    panel.style.top = '';
    panel.style.left = '';
    panel.style.right = '';
    panel.style.bottom = '';
    panel.style.zIndex = '';
    panel.style.margin = '';
    panel.style.width = '';
    panel.style.removeProperty('pointer-events');
    panel.style.removeProperty('visibility');
    delete panel.dataset.nttuFloatOpen;
}

function positionPanel(panel, trigger, align = 'end') {
    const triggerRect = trigger.getBoundingClientRect();
    const { width: panelWidth, height: panelHeight } = measurePanel(panel);

    let top = triggerRect.bottom + GAP;
    let left = align === 'start' ? triggerRect.left : triggerRect.right - panelWidth;

    if (top + panelHeight > window.innerHeight - PAD) {
        const above = triggerRect.top - panelHeight - GAP;
        if (above >= PAD || above > top) {
            top = above;
        }
    }

    if (left + panelWidth > window.innerWidth - PAD) {
        left = window.innerWidth - panelWidth - PAD;
    }
    if (left < PAD) {
        left = PAD;
    }

    top = Math.max(PAD, Math.min(top, window.innerHeight - panelHeight - PAD));

    const host = panel._floatPinnedHost?.isConnected ? panel._floatPinnedHost : null;
    if (host) {
        host.classList.add('nttu-float-host-open');
        host.style.zIndex = String(FLOAT_HOST_Z);
    }

    panel.style.position = 'fixed';
    panel.style.zIndex = String(FLOAT_PANEL_Z);
    panel.style.top = `${Math.round(top)}px`;
    panel.style.left = `${Math.round(left)}px`;
    panel.style.right = 'auto';
    panel.style.bottom = 'auto';
    panel.style.margin = '0';
    panel.style.display = 'block';
    panel.style.visibility = 'visible';
    panel.style.pointerEvents = 'auto';
    panel.removeAttribute('hidden');

    if (panel.dataset.floatMatchWidth === '1') {
        const computed = window.getComputedStyle(panel);
        const minWidth = Number.parseInt(panel.style.minWidth, 10)
            || Number.parseInt(computed.minWidth, 10)
            || 280;
        panel.style.width = `${Math.max(Math.round(triggerRect.width), minWidth)}px`;
    }

    panel.dataset.nttuFloatOpen = '1';
}

function hideFloatedPanel(panel) {
    clearFloatPosition(panel);
    panel.style.display = 'none';
    panel.style.pointerEvents = 'none';
}

function closeExpression(expression) {
    const trimmed = String(expression || '').trim();
    if (!trimmed) {
        return null;
    }

    const bracket = trimmed.match(/^([\w.$]+)\[\s*(['"]?)([\w-]+)\2\s*\]$/);
    if (bracket) {
        const [, obj, , key] = bracket;
        return `${obj} = { ...${obj}, '${key}': false }`;
    }

    if (trimmed.includes('&&')) {
        const parts = trimmed.split('&&').map((part) => part.trim());
        for (const part of parts) {
            if (/^[\w]+$/.test(part)) {
                return `${part} = false`;
            }
        }
        for (const part of parts) {
            if (part.includes('===')) {
                const varName = part.split('===')[0].trim();
                if (/^[\w]+$/.test(varName)) {
                    return `${varName} = null`;
                }
            }
        }
    }

    if (trimmed.includes('===')) {
        const assignTarget = trimmed.split('===')[0].trim();
        if (/^[\w]+$/.test(assignTarget)) {
            return `${assignTarget} = null`;
        }
    }

    if (trimmed.includes('!==')) {
        const assignTarget = trimmed.split('!==')[0].trim();
        if (/^[\w]+$/.test(assignTarget)) {
            return `${assignTarget} = null`;
        }
    }

    if (/^[\w]+$/.test(trimmed)) {
        return `${trimmed} = false`;
    }

    return null;
}

function eventInPath(event, node) {
    if (!node) {
        return false;
    }

    const path = typeof event.composedPath === 'function' ? event.composedPath() : [event.target];
    return path.includes(node);
}

function bindOutsideDismiss(panel, trigger, Alpine, closeExpressionText) {
    const generation = (panel._floatDismissGen || 0) + 1;
    panel._floatDismissGen = generation;

    const handler = (event) => {
        if (panel._floatDismissGen !== generation) {
            return;
        }

        const host = panel._floatPinnedHost?.isConnected ? panel._floatPinnedHost : null;

        if (eventInPath(event, panel) || eventInPath(event, trigger) || eventInPath(event, host)) {
            return;
        }

        Alpine.evaluate(trigger, closeExpressionText);
    };

    document.addEventListener('click', handler, true);

    return () => {
        document.removeEventListener('click', handler, true);

        if (panel._floatDismissGen === generation) {
            panel._floatDismissGen = generation + 1;
        }
    };
}

export function registerFloatingMenu(Alpine) {
    Alpine.directive('float', (el, { modifiers, expression }, { effect, evaluate, cleanup }) => {
        const align = modifiers.includes('start') ? 'start' : 'end';
        const closeExpressionText = el.dataset.floatClose || closeExpression(expression);
        let scrollHandler = null;
        let resizeHandler = null;
        let outsideCleanup = null;
        let outsideBindTimer = null;
        let rafIds = [];

        pinFloatAnchors(el);
        hideFloatedPanel(el);

        const clearRafs = () => {
            rafIds.forEach((id) => window.cancelAnimationFrame(id));
            rafIds = [];
        };

        const reposition = () => {
            const trigger = findTrigger(el);
            if (!trigger) {
                hideFloatedPanel(el);
                return;
            }

            positionPanel(el, trigger, align);
        };

        const bindScrollListeners = () => {
            if (scrollHandler) {
                return;
            }

            scrollHandler = () => reposition();
            resizeHandler = () => reposition();
            window.addEventListener('scroll', scrollHandler, true);
            window.addEventListener('resize', resizeHandler);
        };

        const unbindScrollListeners = () => {
            if (scrollHandler) {
                window.removeEventListener('scroll', scrollHandler, true);
                scrollHandler = null;
            }
            if (resizeHandler) {
                window.removeEventListener('resize', resizeHandler);
                resizeHandler = null;
            }
        };

        const unbindOutside = () => {
            if (outsideBindTimer) {
                window.clearTimeout(outsideBindTimer);
                outsideBindTimer = null;
            }
            if (outsideCleanup) {
                outsideCleanup();
                outsideCleanup = null;
            }
        };

        const bindOutside = () => {
            if (!closeExpressionText) {
                return;
            }

            unbindOutside();

            outsideBindTimer = window.setTimeout(() => {
                outsideBindTimer = null;
                const trigger = findTrigger(el);
                if (!trigger) {
                    return;
                }

                outsideCleanup = bindOutsideDismiss(el, trigger, Alpine, closeExpressionText);
            }, 0);
        };

        effect(() => {
            const open = expression ? !!evaluate(expression) : false;

            if (open) {
                bindScrollListeners();
                reposition();
                clearRafs();
                rafIds.push(requestAnimationFrame(reposition));
                bindOutside();
            } else {
                clearRafs();
                unbindOutside();
                unbindScrollListeners();
                hideFloatedPanel(el);
            }
        });

        cleanup(() => {
            clearRafs();
            unbindOutside();
            unbindScrollListeners();
            hideFloatedPanel(el);
            delete el._floatPinnedHost;
            delete el._floatHost;
            delete el._floatTrigger;
        });
    });
}
