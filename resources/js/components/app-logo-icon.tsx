import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    const { className = '', ...rest } = props;

    return (
        <img
            src="/images/logo.png"
            alt="Kaldi's Coffee Logo"
            className={`object-contain ${className}`}
            {...rest}
        />
    );
}
