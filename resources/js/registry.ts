/// <reference types="@lattice-php/lattice/vite-client" />
import { eagerComponent, extendRegistry, registry as packageRegistry } from "@lattice-php/lattice";
import { DEFAULT_NAMESPACE, UI_NAMESPACE } from "@lattice-php/ui/i18n/instance";
import plugins from "virtual:lattice/plugins";
import PaddleCheckout from "@/components/paddle-checkout";

export const registry = extendRegistry(packageRegistry, ...plugins, {
    components: {
        "billing.checkout": eagerComponent(PaddleCheckout),
    },
    name: "app",
});

/**
 * Every namespace i18next has to fetch. A package that ships translations names
 * its namespace on its plugin, so deriving them from the discovered plugins is
 * what stops a newly installed package's strings from silently rendering in
 * English while the rest of the page is translated. Only this app's own
 * namespaces are listed by hand.
 */
export const i18nNamespaces = [
    DEFAULT_NAMESPACE,
    UI_NAMESPACE,
    ...plugins.flatMap((plugin) => plugin.i18n?.namespace ?? []),
    "tenant",
    "billing",
];
