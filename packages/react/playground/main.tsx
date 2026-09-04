import { StrictMode, useState } from 'react'
import { createRoot } from 'react-dom/client'
import * as Outline24 from '@heroicons/react/24/outline'
import * as Solid24 from '@heroicons/react/24/solid'
import * as Mini20 from '@heroicons/react/20/solid'
import * as Micro16 from '@heroicons/react/16/solid'
import {
  Alert,
  Badge,
  Button,
  createIconRegistry,
  Dropdown,
  DropdownItem,
  Icon,
  IconProvider,
  Input,
  Modal,
  Panel,
  PanelRow,
  Skeleton,
  Tab,
  TabList,
  TabPanel,
  Tabs,
  Toast,
  ToastRegion,
} from '../src'
import './app.css'

/*
 * The playground is the one place that legitimately wants every icon: it exists
 * to look at things, and typing out a named import per glyph would make trying
 * one a chore. Namespace imports cost about a megabyte and defeat tree-shaking
 * entirely, which is exactly why applications declare what they use instead —
 * see createIconRegistry. Nothing here ships.
 */
const icons = createIconRegistry({
  outline: Outline24,
  solid: Solid24,
  mini: Mini20,
  micro: Micro16,
})

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
  const [modal, setModal] = useState<string | null>(null)
  const [toasts, setToasts] = useState<number[]>([])

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

      <Section title="Tabs">
        <div className="w-full">
          <Tabs label="Employee sections" defaultValue="details">
            <TabList label="Employee sections">
              <Tab value="details">Details</Tab>
              <Tab value="roles" count={2}>
                Roles
              </Tab>
              <Tab value="history">Change history</Tab>
              <Tab value="closed" disabled>
                Disabled
              </Tab>
            </TabList>
            <TabPanel value="details">
              <p className="text-body">Arrow keys move between the tabs.</p>
            </TabPanel>
            <TabPanel value="roles">
              <p className="text-body">The unselected panels are not in the tab order at all.</p>
            </TabPanel>
            <TabPanel value="history">
              <p className="text-body">Third panel.</p>
            </TabPanel>
            <TabPanel value="closed">
              <p className="text-body">Unreachable.</p>
            </TabPanel>
          </Tabs>
        </div>

        <div className="w-full">
          {/* Links, so a nav — not a tablist. specs/tabs.md leads with this. */}
          <Tabs label="Pages" navigation>
            <Tab href="#roles" active>
              Roles
            </Tab>
            <Tab href="#employees">Employees</Tab>
          </Tabs>
        </div>
      </Section>

      <Section title="Dropdown">
        <Dropdown trigger={<Button variant="secondary">Actions</Button>}>
          <DropdownItem icon="pencil-square" href="#edit">
            Edit
          </DropdownItem>
          <DropdownItem icon="document-duplicate">Duplicate</DropdownItem>
          <DropdownItem icon="archive-box" disabled>
            Archive
          </DropdownItem>
          <DropdownItem icon="trash" tone="danger">
            Delete
          </DropdownItem>
        </Dropdown>

        <Dropdown placement="bottom-start" trigger={<Button variant="ghost">Opens left-aligned</Button>}>
          <DropdownItem>One</DropdownItem>
          <DropdownItem>Two</DropdownItem>
        </Dropdown>
      </Section>

      <Section title="Modal">
        <Button onClick={() => setModal('md')}>Open</Button>
        <Button variant="danger" onClick={() => setModal('confirm')}>
          Confirm (not dismissible)
        </Button>

        <Modal
          open={modal === 'md'}
          onOpenChange={(next) => setModal(next ? 'md' : null)}
          title="Assign a role"
          description="The assignment takes effect on the date you choose."
          footer={
            <>
              <Button variant="ghost" onClick={() => setModal(null)}>
                Cancel
              </Button>
              <Button onClick={() => setModal(null)}>Assign</Button>
            </>
          }
        >
          <div className="flex flex-col gap-4 py-1">
            <Input label="Role" as="select">
              <option>Store In-charge</option>
              <option>Shift In-charge</option>
            </Input>
            <Input label="Effective from" type="date" />
          </div>
        </Modal>

        <Modal
          open={modal === 'confirm'}
          onOpenChange={(next) => setModal(next ? 'confirm' : null)}
          size="sm"
          dismissible={false}
          title="Deactivate role?"
          description="This cannot be undone from here."
          footer={
            <>
              <Button variant="ghost" onClick={() => setModal(null)}>
                Cancel
              </Button>
              <Button variant="danger" onClick={() => setModal(null)}>
                Deactivate
              </Button>
            </>
          }
        >
          Escape and the backdrop do nothing — the footer is the only way out.
        </Modal>
      </Section>

      <Section title="Toast">
        <Button variant="secondary" onClick={() => setToasts((all) => [...all, Date.now()])}>
          Raise one
        </Button>
        <p className="text-meta text-fg-muted">
          The region is always rendered, bottom-right. Timers belong to the host.
        </p>
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

      <ToastRegion>
        {toasts.map((id) => (
          <Toast
            key={id}
            tone="success"
            title="Saved"
            onDismiss={() => setToasts((all) => all.filter((t) => t !== id))}
          >
            The role was assigned.
          </Toast>
        ))}
      </ToastRegion>
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
    <IconProvider registry={icons}>
      <App />
    </IconProvider>
  </StrictMode>,
)
