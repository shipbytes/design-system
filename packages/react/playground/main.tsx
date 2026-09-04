import { StrictMode, useState } from 'react'
import { createRoot } from 'react-dom/client'
import { Alert, Badge, Button, Icon, Input, Panel, PanelRow, Skeleton } from '../src'
import './app.css'

const tones = ['neutral', 'accent', 'success', 'warning', 'danger'] as const

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="flex flex-col gap-3">
      <h2 className="text-section text-fg">{title}</h2>
      <div className="flex flex-wrap items-center gap-3">{children}</div>
    </section>
  )
}

function Gallery() {
  const [loading, setLoading] = useState(false)

  return (
    <div className="flex flex-col gap-10 p-8">
      <Section title="Button">
        <Button variant="primary">Primary</Button>
        <Button variant="secondary">Secondary</Button>
        <Button variant="ghost">Ghost</Button>
        <Button variant="danger">Danger</Button>
        <Button size="sm">Small</Button>
        <Button size="lg">Large</Button>
        <Button disabled>Disabled</Button>
        <Button loading={loading} onClick={() => setLoading((v) => !v)}>
          Toggle loading
        </Button>
        <Button iconOnly aria-label="Add">
          <Icon name="plus" />
        </Button>
        <Button href="#">A link</Button>
      </Section>

      <Section title="Badge">
        {tones.map((tone) => (
          <Badge key={tone} tone={tone} dot>
            {tone}
          </Badge>
        ))}
        {tones.map((tone) => (
          <Badge key={`solid-${tone}`} tone={tone} variant="solid">
            {tone}
          </Badge>
        ))}
        <Badge variant="outline">category</Badge>
      </Section>

      <Section title="Icon">
        {[3, 4, 4.5, 5, 6, 8].map((size) => (
          <Icon key={size} name="beaker" size={size} />
        ))}
        <Icon name="x" />
        <Icon name="check-circle" variant="solid" size={6} />
        <Icon name="chevron-down" variant="mini" />
      </Section>

      <div className="grid gap-4 sm:grid-cols-2">
        <Panel title="List panel" action="View all" actionHref="#">
          <PanelRow href="#">
            <Icon name="cube" />
            <span className="min-w-0 flex-1 truncate">A row that navigates</span>
            <Badge tone="success" dot>
              Active
            </Badge>
          </PanelRow>
          <PanelRow>
            <Icon name="cube" />
            <span className="min-w-0 flex-1 truncate">A row that does not</span>
          </PanelRow>
        </Panel>

        <Panel title="Feature panel" subtitle="With a leading tile" icon="chart-bar" body="plain">
          <p className="text-body">Free-form content in a padded body.</p>
        </Panel>
      </div>

      <Section title="Input">
        <div className="grid w-full gap-4 sm:grid-cols-3">
          <Input label="Login ID" placeholder="e.g. rdeshmukh" icon="user" />
          <Input label="Code" error="Code must be uppercase." defaultValue="grn" />
          <Input label="Note" help="Optional." as="textarea" />
          <Input label="Scope" as="select" defaultValue="S">
            <option value="S">Shared</option>
            <option value="P">Plant-specific</option>
          </Input>
          <Input label="Disabled" disabled defaultValue="Read only" />
        </div>
      </Section>

      <Section title="Alert">
        <div className="flex w-full flex-col gap-3">
          <Alert tone="accent">Something the reader should know.</Alert>
          <Alert tone="success" title="Saved">
            The reason code was created.
          </Alert>
          <Alert tone="warning" dismissible>
            Contractor licence expires in 3 days.
          </Alert>
          <Alert tone="danger" title="Save failed">
            This record was updated by someone else.
          </Alert>
        </div>
      </Section>

      <Section title="Skeleton">
        <div className="grid w-full gap-4 sm:grid-cols-3">
          <div aria-busy="true" aria-live="polite">
            <Skeleton lines={4} />
          </div>
          <Skeleton variant="block" />
          <Skeleton variant="circle" size="lg" />
        </div>
      </Section>
    </div>
  )
}

function App() {
  const [dark, setDark] = useState(false)

  return (
    <div className={dark ? 'dark' : ''}>
      <div className="min-h-dvh bg-ground text-fg-body">
        <div className="flex items-center justify-between border-b border-border px-8 py-4">
          <h1 className="text-page text-fg">@shipbytes/react</h1>
          <Button variant="secondary" onClick={() => setDark((v) => !v)}>
            <Icon name={dark ? 'sun' : 'moon'} />
            {dark ? 'Light' : 'Dark'}
          </Button>
        </div>
        <Gallery />
      </div>
    </div>
  )
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
