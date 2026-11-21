import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "@/lib/utils"
import { Slot } from "radix-ui"

const badgeVariants = cva(
  "inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-md border border-transparent px-2 py-0.5 text-xs font-medium whitespace-nowrap transition-[color,box-shadow] focus-visible:ring-[3px] focus-visible:ring-ring/40 [&>svg]:pointer-events-none [&>svg]:size-3.5",
  {
    variants: {
      variant: {
        default: "bg-index-50 text-index-700 ring-1 ring-index-500/15 ring-inset dark:bg-index-500/10 dark:text-index-300 dark:ring-index-400/20",
        secondary: "bg-zinc-100 text-zinc-600 ring-1 ring-zinc-500/10 ring-inset dark:bg-white/[0.06] dark:text-zinc-300 dark:ring-white/10",
        destructive: "bg-red-50 text-red-700 ring-1 ring-red-600/15 ring-inset dark:bg-red-500/10 dark:text-red-400 dark:ring-red-400/20",
        outline: "border-border text-foreground",
        exito: "bg-green-50 text-green-700 ring-1 ring-green-600/15 ring-inset dark:bg-green-500/10 dark:text-green-400 dark:ring-green-400/20",
        aviso: "bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 ring-inset dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20",
        info: "bg-sky-50 text-sky-700 ring-1 ring-sky-600/15 ring-inset dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-400/20",
        ghost: "[a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        link: "text-primary underline-offset-4 [a&]:hover:underline",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant = "default",
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot.Root : "span"

  return (
    <Comp
      data-slot="badge"
      data-variant={variant}
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
