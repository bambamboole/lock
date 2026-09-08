import type { RendererComponent } from "@lattice-php/lattice";
import { apiJson } from "@lattice-php/core/api";
import { Button } from "@lattice-php/ui";
import { useCallback, useEffect } from "react";

declare global {
    interface Window {
        Paddle?: {
            Environment: { set: (environment: string) => void };
            Initialize: (options: { token: string }) => void;
            Checkout: { open: (options: unknown) => void };
        };
    }
}

let paddleReady: Promise<void> | undefined;

function ensurePaddle(token: string, sandbox: boolean): Promise<void> {
    paddleReady ??= new Promise((resolve) => {
        const init = () => {
            if (!window.Paddle) {
                return;
            }
            if (sandbox) {
                window.Paddle.Environment.set("sandbox");
            }
            window.Paddle.Initialize({ token });
            resolve();
        };

        if (window.Paddle) {
            init();
            return;
        }

        const script = document.createElement("script");
        script.id = "paddle-js";
        script.src = "https://cdn.paddle.com/paddle/v2/paddle.js";
        script.addEventListener("load", init);
        document.head.appendChild(script);
    });

    return paddleReady;
}

const PaddleCheckout: RendererComponent<"billing.checkout"> = ({ node }) => {
    const { label, checkoutUrl, priceId, token, sandbox } = node.props;

    useEffect(() => {
        void ensurePaddle(token, sandbox);
    }, [token, sandbox]);

    const openCheckout = useCallback(async () => {
        await ensurePaddle(token, sandbox);

        let options: unknown;
        try {
            ({ options } = await apiJson<{ options: unknown }>(checkoutUrl, {
                method: "POST",
                body: JSON.stringify({ price_id: priceId }),
            }));
        } catch {
            return;
        }

        window.Paddle?.Checkout.open(options);
    }, [checkoutUrl, priceId, token, sandbox]);

    return (
        <Button type="button" onClick={openCheckout} data-testid="paddle-checkout">
            {label}
        </Button>
    );
};

export default PaddleCheckout;
