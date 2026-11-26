import { Button } from '../ui/Button';

export function ActiveToggleButton({
    active,
    pending,
    confirmMessage,
    ariaLabel,
    onClick,
}: {
    active: boolean;
    pending: boolean;
    confirmMessage: string;
    ariaLabel: string;
    onClick: () => void;
}) {
    return (
        <Button
            size="sm"
            variant="secondary"
            loading={pending}
            aria-label={ariaLabel}
            onClick={() => {
                if (!active || window.confirm(confirmMessage)) {
                    onClick();
                }
            }}
        >
            {active ? 'Nonaktifkan' : 'Aktifkan'}
        </Button>
    );
}
